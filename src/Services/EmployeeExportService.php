<?php

namespace Athka\Employees\Services;

use Athka\Employees\Models\Employee;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Exception;

class EmployeeExportService
{
    /**
     * Sanitizes values against CSV/XLSX Formula Injection (OWASP CSV Injection).
     * If a cell begins with '=', '+', '-', '@', '\t', or '\r', prefix it with a single quote '\''.
     */
    public static function sanitizeFormulaInjection(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        $firstChar = substr($value, 0, 1);
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * Determine allowed export columns based on user permissions.
     */
    public function getAllowedColumns(?Authenticatable $user): array
    {
        $columns = [
            'employee_no' => 'رقم الموظف',
            'name_ar' => 'الاسم بالعربية',
            'name_en' => 'الاسم بالإنجليزية',
            'national_id' => 'رقم الهوية/الإقامة',
            'gender' => 'الجنس',
            'mobile' => 'رقم الجوال',
            'email_work' => 'البريد الإلكتروني للعمل',
            'status' => 'الحالة',
            'hired_at' => 'تاريخ التعيين',
        ];

        // Financial fields are restricted to employees.contracts.manage permission
        if ($user && method_exists($user, 'can') && $user->can('employees.contracts.manage')) {
            $columns['basic_salary'] = 'الراتب الأساسي';
            $columns['allowances'] = 'البدلات';
            $columns['contract_type'] = 'نوع العقد';
        }

        return $columns;
    }

    /**
     * Generate structured CSV export data safely and atomically.
     *
     * @param Builder $query Eloquent query for employees
     * @param Authenticatable|null $user Current authenticated user
     * @return array{headers: array, rows: array, count: int}
     * @throws Exception If exported rows count does not match database query count
     */
    public function buildExportData(Builder $query, ?Authenticatable $user): array
    {
        $allowedCols = $this->getAllowedColumns($user);
        $headers = array_values($allowedCols);
        $keys = array_keys($allowedCols);

        $hasLimit = $query->getQuery()->limit > 0;
        $dbCount = $hasLimit
            ? (int) $query->getQuery()->limit
            : (int) $query->toBase()->getCountForPagination();

        $rows = [];
        $exportedCount = 0;

        $processItem = function ($employee) use ($keys, &$rows, &$exportedCount) {
            $row = [];
            foreach ($keys as $key) {
                $val = $employee->{$key} ?? '';
                if ($key === 'hired_at' && $val instanceof \DateTimeInterface) {
                    $val = $val->format('Y-m-d');
                }
                $row[] = self::sanitizeFormulaInjection((string) $val);
            }
            $rows[] = $row;
            $exportedCount++;
        };

        if ($hasLimit) {
            foreach ($query->get() as $employee) {
                $processItem($employee);
            }
        } else {
            $query->chunk(200, function ($employees) use ($processItem) {
                foreach ($employees as $employee) {
                    $processItem($employee);
                }
            });
        }

        // Strict assertion: Export rows count MUST equal DB query rows count
        if ($exportedCount !== $dbCount) {
            throw new Exception("Export row count mismatch: Expected {$dbCount} rows, but exported {$exportedCount} rows.");
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'count' => $exportedCount,
        ];
    }
}
