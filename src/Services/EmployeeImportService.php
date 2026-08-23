<?php

namespace Athka\Employees\Services;

use Athka\Employees\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class EmployeeImportService
{
    /**
     * Security Limits
     */
    public int $maxFileSizeBytes = 10 * 1024 * 1024; // 10MB
    public int $maxRows = 2000;
    public int $maxColumns = 50;
    public int $chunkSize = 100;

    /**
     * Process employee import in chunks with optional dry run and idempotency.
     *
     * @param string $filePath Absolute path to CSV file
     * @param int $companyId Target SaaS company ID
     * @param bool $isDryRun If true, validates rows without persisting changes
     * @return array{ok: bool, total_rows: int, processed: int, created: int, updated: int, errors: array}
     */
    public function importFromCsv(string $filePath, int $companyId, bool $isDryRun = false): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'ok' => false,
                'error' => 'file_not_found',
                'message' => 'Import file does not exist or is not readable.',
            ];
        }

        if (filesize($filePath) > $this->maxFileSizeBytes) {
            return [
                'ok' => false,
                'error' => 'file_too_large',
                'message' => "File size exceeds maximum limit of " . ($this->maxFileSizeBytes / (1024 * 1024)) . "MB.",
            ];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [
                'ok' => false,
                'error' => 'cannot_open_file',
                'message' => 'Unable to open import file.',
            ];
        }

        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return [
                'ok' => false,
                'error' => 'empty_file',
                'message' => 'Import file is empty.',
            ];
        }

        if (count($header) > $this->maxColumns) {
            fclose($handle);
            return [
                'ok' => false,
                'error' => 'too_many_columns',
                'message' => "Columns count exceeds maximum limit of {$this->maxColumns}.",
            ];
        }

        // Normalize headers
        $header = array_map(fn($col) => strtolower(trim((string)$col)), $header);

        $rowCount = 0;
        $processedCount = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $errors = [];
        $chunk = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;

            if ($rowCount > $this->maxRows) {
                fclose($handle);
                return [
                    'ok' => false,
                    'error' => 'row_limit_exceeded',
                    'message' => "Row count exceeds maximum allowed limit of {$this->maxRows} rows.",
                ];
            }

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $rowData = [];
            foreach ($header as $index => $colName) {
                $rowData[$colName] = isset($row[$index]) ? trim((string)$row[$index]) : '';
            }

            $chunk[] = [
                'line' => $rowCount + 1, // 1-indexed for header
                'data' => $rowData,
            ];

            if (count($chunk) >= $this->chunkSize) {
                $res = $this->processChunk($chunk, $companyId, $isDryRun);
                $processedCount += $res['processed'];
                $createdCount += $res['created'];
                $updatedCount += $res['updated'];
                $errors = array_merge($errors, $res['errors']);
                $chunk = [];
            }
        }

        if (!empty($chunk)) {
            $res = $this->processChunk($chunk, $companyId, $isDryRun);
            $processedCount += $res['processed'];
            $createdCount += $res['created'];
            $updatedCount += $res['updated'];
            $errors = array_merge($errors, $res['errors']);
        }

        fclose($handle);

        return [
            'ok' => empty($errors),
            'dry_run' => $isDryRun,
            'total_rows' => $rowCount,
            'processed' => $processedCount,
            'created' => $createdCount,
            'updated' => $updatedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Process a chunk of rows atomically.
     */
    protected function processChunk(array $chunk, int $companyId, bool $isDryRun): array
    {
        $processed = 0;
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($chunk as $item) {
            $line = $item['line'];
            $data = $item['data'];

            $nameAr = $data['name_ar'] ?? $data['name'] ?? '';
            $nationalId = $data['national_id'] ?? $data['id_number'] ?? '';
            $employeeCode = $data['employee_code'] ?? $data['code'] ?? '';

            $validator = Validator::make($data, [
                'name_ar' => ['nullable', 'string', 'max:255'],
                'name' => ['nullable', 'string', 'max:255'],
                'national_id' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255'],
            ]);

            if (empty($nameAr)) {
                $errors[] = [
                    'line' => $line,
                    'error' => 'missing_name',
                    'message' => "Row #{$line}: Employee name (name_ar/name) is required.",
                ];
                continue;
            }

            if ($validator->fails()) {
                $errors[] = [
                    'line' => $line,
                    'error' => 'validation_error',
                    'message' => "Row #{$line}: " . implode(', ', $validator->errors()->all()),
                ];
                continue;
            }

            $processed++;

            if (!$isDryRun) {
                DB::transaction(function () use ($companyId, $nationalId, $employeeCode, $nameAr, $data, &$created, &$updated) {
                    $matchCriteria = ['saas_company_id' => $companyId];
                    if (!empty($nationalId)) {
                        $matchCriteria['national_id'] = $nationalId;
                    } elseif (!empty($employeeCode)) {
                        $matchCriteria['employee_code'] = $employeeCode;
                    } else {
                        $matchCriteria['name_ar'] = $nameAr;
                    }

                    $existing = Employee::withoutGlobalScope('active_only')
                        ->where($matchCriteria)
                        ->first();

                    $deptId = !empty($data['department_id']) ? (int)$data['department_id'] : (int) DB::table('departments')->where('saas_company_id', $companyId)->value('id');
                    $jobId  = !empty($data['job_title_id'])  ? (int)$data['job_title_id']  : (int) DB::table('job_titles')->where('saas_company_id', $companyId)->value('id');

                    $payload = [
                        'saas_company_id' => $companyId,
                        'name_ar' => $nameAr,
                        'name_en' => $data['name_en'] ?? null,
                        'national_id' => !empty($nationalId) ? $nationalId : null,
                        'nationality' => !empty($data['nationality']) ? $data['nationality'] : 'اليمن',
                        'gender' => !empty($data['gender']) ? $data['gender'] : 'MALE',
                        'marital_status' => !empty($data['marital_status']) ? $data['marital_status'] : 'single',
                        'sector' => !empty($data['sector']) ? $data['sector'] : 'private',
                        'grade' => !empty($data['grade']) ? $data['grade'] : 'G1',
                        'job_function' => !empty($data['job_function']) ? $data['job_function'] : 'administrative',
                        'job_level' => !empty($data['job_level']) ? $data['job_level'] : 'junior',
                        'branch_id' => !empty($data['branch_id']) ? (int)$data['branch_id'] : (int) DB::table('branches')->where('saas_company_id', $companyId)->value('id'),
                        'department_id' => $deptId > 0 ? $deptId : null,
                        'job_title_id' => $jobId > 0 ? $jobId : null,
                        'birth_date' => !empty($data['birth_date']) ? $data['birth_date'] : '1990-01-01',
                        'hired_at' => !empty($data['hired_at']) ? $data['hired_at'] : now()->toDateString(),
                        'contract_type' => !empty($data['contract_type']) ? $data['contract_type'] : 'full_time',
                        'email_work' => $data['email'] ?? $data['email_work'] ?? null,
                        'mobile' => $data['mobile'] ?? $data['phone'] ?? '0000000000',
                        'city' => $data['city'] ?? 'صنعاء',
                        'district' => $data['district'] ?? 'المركز',
                        'emergency_contact_name' => $data['emergency_contact_name'] ?? 'N/A',
                        'emergency_contact_phone' => $data['emergency_contact_phone'] ?? '0000000000',
                        'emergency_contact_relation' => $data['emergency_contact_relation'] ?? 'other',
                        'basic_salary' => isset($data['basic_salary']) && is_numeric($data['basic_salary']) ? (float)$data['basic_salary'] : null,
                        'status' => 'ACTIVE',
                    ];

                    if ($existing) {
                        $existing->update($payload);
                        $updated++;
                    } else {
                        Employee::create($payload);
                        $created++;
                    }
                });
            }
        }

        return [
            'processed' => $processed,
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }
}
