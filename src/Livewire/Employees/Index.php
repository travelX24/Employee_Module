<?php

namespace Athka\Employees\Livewire\Employees;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Athka\Employees\Models\Employee;

class Index extends Component
{
    use WithPagination, \Livewire\WithFileUploads;
 
    protected string $paginationTheme = 'tailwind';
 
    public string $search = '';
 
    public string $departmentId = 'all';
    public string $jobTitleId   = 'all';
    public string $status       = 'all'; 

    public string $branchFilterId = 'all';
    public string $contractType   = 'all'; 


 
    //   Import
    public bool $showImportModal = false;
    public $importFile;
    public array $importValidationErrors = [];
    public bool $isImporting = false;

    //   فلتر تاريخ التعيين
    public string $hiringDateType = 'all'; // all | this_month | last_3_months | this_year | custom
    public ?string $hiringDateStart = null;
    public ?string $hiringDateEnd   = null;
 
    //   متغيرات نافذة إلغاء التفعيل / التعطيل
    public bool $showDeactivateModal = false;
    public ?Employee $selectedEmployee = null;
    public string $deactivateReason = '';
    public string $deactivateDate = '';
    public string $deactivateNotes = '';
 
    //   متغيرات نافذة إنهاء الخدمة (الأرشفة / التسريح)
    public bool $showTerminationModal = false;
    public string $terminationType = ''; // RESIGNATION, TERMINATION, RETIREMENT, DEATH, CONTRACT_END
    public string $terminationDate = '';
    public string $terminationReason = '';
    
    // المستحقات المالية
    public $dueSalary = 0;
    public $dueVacation = 0;
    public $dueOthers = 0;
 
    public string $viewMode = 'list'; // list | cards
 
    //   Export
    public bool $showExportModal = false;
    public string $exportFormat = 'excel'; // excel | pdf
    public string $exportScope = 'all'; // all | custom
    public array $selectedFields = [];
    
    public function getAvailableFieldsProperty(): array
    {
        return [
            'employee_no' => tr('Employee Number'),
            'name_ar' => tr('Arabic Name'),
            'name_en' => tr('English Name'),
            'national_id' => tr('National ID'),
            'national_id_expiry' => tr('National ID Expiry'),
            'nationality' => tr('Nationality'),
            'gender' => tr('Gender'),
            'marital_status' => tr('Social Status'),
            'birth_date' => tr('Birth Date'),
            'birth_place' => tr('Birth Place'),
            'children_count' => tr('Children Count'),
            'hired_at' => tr('Hire Date'),
            'procedures_start_at' => tr('Procedures Start At'),
            'department_id' => tr('Department'),
            'sub_department_id' => tr('Sub Department'),
            'job_title_id' => tr('Job Title'),
            'sector' => tr('Sector'),
            'grade' => tr('Grade'),
            'job_function' => tr('Job Function'),
            'manager_id' => tr('Manager'),
            'basic_salary' => tr('Basic Salary'),
            'allowances' => tr('Allowances'),
            'annual_leave_days' => tr('Annual Leave Days'),
            'contract_type' => tr('Contract Type'),
            'contract_duration_months' => tr('Contract Duration (Months)'),
            'status' => tr('Status'),
            'ended_at' => tr('Ended At'),
            'mobile' => tr('Mobile'),
            'mobile_alt' => tr('Alternative Mobile'),
            'email_work' => tr('Work Email'),
            'email_personal' => tr('Personal Email'),
            'emergency_contact_name' => tr('Emergency Name'),
            'emergency_contact_phone' => tr('Emergency Phone'),
            'emergency_contact_relation' => tr('Emergency Relation'),
            'city' => tr('City'),
            'district' => tr('District'),
            'address' => tr('Address'),
        ];
    }

    /**
     * ملاحظة:
     * صفحة الشركات تعتمد عملياً على pagination داخل x-ui.table (client-side).
     * لذلك هنا نرفع عدد النتائج في الصفحة الأولى حتى يعمل نفس الإحساس.
     */
    public int $perPage = 200;

    protected $queryString = [
        'search'          => ['except' => ''],
        'departmentId'    => ['except' => 'all'],
        'jobTitleId'      => ['except' => 'all'],
        'status'          => ['except' => 'all'],
        'branchFilterId'  => ['except' => 'all'], 
        'contractType'    => ['except' => 'all'], 
        'hiringDateType'  => ['except' => 'all'],
        'hiringDateStart' => ['except' => null],
        'hiringDateEnd'   => ['except' => null],
        'viewMode'        => ['except' => 'list'],
        'page'            => ['except' => 1],
    ];

    protected $listeners = ['employee-updated' => '$refresh'];

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['list', 'cards'], true)) {
            $this->viewMode = $mode;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDepartmentId(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingHiringDateType(): void
    {
        $this->resetPage();
        
        // تعيين التواريخ بناءً على الفلتر الجاهز
        switch ($this->hiringDateType) {
            case 'this_month':
                $this->hiringDateStart = now()->startOfMonth()->format('Y-m-d');
                $this->hiringDateEnd   = now()->endOfMonth()->format('Y-m-d');
                break;
            case 'last_3_months':
                $this->hiringDateStart = now()->subMonths(3)->startOfDay()->format('Y-m-d');
                $this->hiringDateEnd   = now()->endOfDay()->format('Y-m-d');
                break;
            case 'this_year':
                $this->hiringDateStart = now()->startOfYear()->format('Y-m-d');
                $this->hiringDateEnd   = now()->endOfYear()->format('Y-m-d');
                break;
            case 'custom':
                // لا نغير التواريخ، ننتظر تحديد المستخدم
                break;
            default:
                $this->hiringDateStart = null;
                $this->hiringDateEnd   = null;
        }
    }

    //   نفس اسم Companies لزر Clear all filters
    public function clearAllFilters(): void
    {
        $this->search = '';
        $this->departmentId = 'all';
        $this->jobTitleId = 'all';
        $this->status = 'all';

        $this->branchFilterId = 'all'; 
        $this->contractType   = 'all'; 

        $this->hiringDateType = 'all';
        $this->hiringDateStart = null;
        $this->hiringDateEnd = null;

        $this->resetPage();
    }

    //   إبقاء resetFilters للتوافق (لو فيه أماكن تستدعيها)
    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'departmentId',
            'jobTitleId',
            'status',
            'branchFilterId', 
            'contractType', 
            'hiringDateType',
            'hiringDateStart',
            'hiringDateEnd',
        ]);

        $this->resetPage();
    }

    public function openExportModal()
    {
        $this->selectedFields = array_keys($this->availableFields);
        $this->showExportModal = true;
    }

    public function export()
    {
        $companyId = auth()->user()->saas_company_id;
        
        $allowed = DB::table('branch_user_access')
            ->where('user_id', Auth::id())
            ->where('saas_company_id', $companyId)
            ->pluck('branch_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $query = Employee::where('saas_company_id', $companyId)
            ->when(! empty($allowed), fn ($q) => $q->whereIn('branch_id', $allowed));        
        // Apply existing filters
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name_ar', 'like', '%' . $this->search . '%')
                  ->orWhere('name_en', 'like', '%' . $this->search . '%')
                  ->orWhere('employee_no', 'like', '%' . $this->search . '%')
                  ->orWhere('national_id', 'like', '%' . $this->search . '%');
            });
        }
        if ($this->departmentId !== 'all') {
            $query->where('department_id', $this->departmentId);
        }
        if ($this->jobTitleId !== 'all') {
            $query->where('job_title_id', $this->jobTitleId);
        }
        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }
        
        // Hiring date filter
        if ($this->hiringDateType !== 'all') {
            if ($this->hiringDateType === 'this_month') {
                $query->whereMonth('hired_at', now()->month)->whereYear('hired_at', now()->year);
            } elseif ($this->hiringDateType === 'last_3_months') {
                $query->where('hired_at', '>=', now()->subMonths(3));
            } elseif ($this->hiringDateType === 'this_year') {
                $query->whereYear('hired_at', now()->year);
            } elseif ($this->hiringDateType === 'custom' && $this->hiringDateStart && $this->hiringDateEnd) {
                $query->whereBetween('hired_at', [$this->hiringDateStart, $this->hiringDateEnd]);
            }
        }

        $employees = $query->with(['department', 'subDepartment', 'jobTitle', 'manager'])->get();
        
        $fieldsToExport = ($this->exportScope === 'all') 
            ? array_keys($this->availableFields) 
            : $this->selectedFields;

        if (empty($fieldsToExport)) {
            $this->dispatch('toast',
                type: 'error',
                title: tr('Export Error'),
                message: tr('Please select at least one field to export.')
            );
            return;
        }

        if ($this->exportFormat === 'excel') {
            return $this->exportToCsv($employees, $fieldsToExport);
        } else {
            return $this->exportToPdf($employees, $fieldsToExport);
        }
    }

    private function exportToCsv($employees, $fields)
    {
        $filename = 'employees_export_' . date('Y-m-d_H-i-s') . '.csv';
        $available = $this->availableFields;
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($employees, $fields, $available) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8 (Excel friendly)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            $headerRow = [];
            foreach ($fields as $field) {
                $headerRow[] = $available[$field] ?? $field;
            }
            fputcsv($file, $headerRow);

            // Data rows
            foreach ($employees as $employee) {
                $row = [];
                foreach ($fields as $field) {
                    $value = '';
                    if ($field === 'department_id') {
                        $value = $employee->department?->name ?? 'N/A';
                    } elseif ($field === 'sub_department_id') {
                        $value = $employee->subDepartment?->name ?? 'N/A';
                    } elseif ($field === 'job_title_id') {
                        $value = $employee->jobTitle?->name ?? 'N/A';
                    } elseif ($field === 'manager_id') {
                        $value = $employee->manager?->name_ar ?? $employee->manager?->name_en ?? 'N/A';
                    } else {
                        $value = $employee->{$field};
                    }
                    $row[] = $value;
                }
                fputcsv($file, $row);
            }
            fclose($file);
        };

        $this->showExportModal = false;
        return response()->stream($callback, 200, $headers);
    }

    private function exportToPdf($employees, $fields)
    {
        // Check if DomPDF is installed
        if (!class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            session()->flash('error', tr('PDF export requires DomPDF library. Please contact administrator.'));
            return;
        }

        $company = \Athka\Saas\Models\SaasCompany::find(auth()->user()->saas_company_id);
        
        // Ensure company has the expected fields mapped for the view if needed, 
        // though we updated the view to use legal_name_ar etc.
        
        $available = $this->availableFields;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('employees::livewire.employees.pdf-export', [
            'employees' => $employees,
            'fields' => $fields,
            'availableFields' => $available,
            'company' => $company,
            'title' => tr('Employees Report'),
            'date' => now()->format('Y/m/d H:i')
        ])->setOption([
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
        ]);

        $pdf->setPaper('a4', 'landscape');
        
        $this->showExportModal = false;
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, 'employees_report_' . date('Y-m-d') . '.pdf');
    }

  
    public function updatingJobTitleId(): void
    {
        $this->resetPage();
    }

    public function updatingBranchFilterId(): void 
    {
        $this->resetPage();
    }

    public function updatingContractType(): void 
    {
        $this->resetPage();
    }


    private function getCompanyId(): int
    {
        if (app()->bound('currentCompany')) {
            return (int) app('currentCompany')->id;
        }

        return (int) (Auth::user()?->saas_company_id ?? 0);
    }

    private function getBranchId(): ?int
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if (($user->access_scope ?? 'all') === 'branch') {
            return $user->branch_id ?? null;
        }

        return null;
    }

    private function departmentModelClass(): string
    {
        return class_exists(\Athka\SystemSettings\Models\Department::class)
            ? \Athka\SystemSettings\Models\Department::class
            : \Athka\SystemSettings\Models\Department::class;
    }

    private function jobTitleModelClass(): string
    {
        return class_exists(\Athka\SystemSettings\Models\JobTitle::class)
            ? \Athka\SystemSettings\Models\JobTitle::class
            : \Athka\SystemSettings\Models\JobTitle::class;
    }

    protected function trp(string $english, array $params = [], string $group = 'ui'): string
    {
        $text = tr($english, $group);

        foreach ($params as $key => $value) {
            $text = str_replace(':' . $key, (string) $value, $text);
        }

        return $text;
    }

    public function render()
    {
        $companyId = $this->getCompanyId();
        $branchId = $this->getBranchId();

        $Department = $this->departmentModelClass();
        $JobTitle   = $this->jobTitleModelClass();

        //   خيارات الفلاتر بصيغة value/label مثل Companies
        $departmentsOptions = $Department::query()
            ->where('saas_company_id', $companyId)
            ->where('is_active', true) // Active only
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($d) => ['value' => (string) $d->id, 'label' => $d->name])
            ->toArray();

        $jobTitlesOptions = $JobTitle::query()
            ->where('saas_company_id', $companyId)
            ->where('is_active', true) // Active only
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($j) => ['value' => (string) $j->id, 'label' => $j->name])
            ->toArray();

        $branchesOptions = [];
        $Branch = $this->branchModelClass();

        if ($Branch) {
            $allowedBranchIds = DB::table('branch_user_access')
                ->where('user_id', Auth::id())
                ->where('saas_company_id', $companyId)
                ->pluck('branch_id')
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            $qBr = $Branch::query()->orderBy('id');

            if (!empty($allowedBranchIds)) {
                $qBr->whereIn('id', $allowedBranchIds);
            }

            // فلترة حسب الشركة لو الأعمدة موجودة
            try {
                $table = (new $Branch)->getTable();
                if (Schema::hasColumn($table, 'saas_company_id')) {
                    $qBr->where('saas_company_id', $companyId);
                } elseif (Schema::hasColumn($table, 'company_id')) {
                    $qBr->where('company_id', $companyId);
                }
            } catch (\Throwable $e) {}

            $isAr = substr((string) app()->getLocale(), 0, 2) === 'ar';

            $branchesOptions = $qBr->get()->map(function ($b) use ($isAr) {
                $label = $isAr
                    ? ($b->name_ar ?? $b->name ?? $b->name_en ?? ('#' . $b->id))
                    : ($b->name_en ?? $b->name ?? $b->name_ar ?? ('#' . $b->id));

                $code = $b->code ?? null;
                if ($code) $label .= ' - ' . $code;

                return ['value' => (string) $b->id, 'label' => $label];
            })->toArray();
        }

        // Query الموظفين
        $allowed = DB::table('branch_user_access')
            ->where('user_id', Auth::id())
            ->where('saas_company_id', $companyId)
            ->pluck('branch_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $employees = Employee::query()
            ->forCompany($companyId)
            ->when(! empty($allowed), fn ($q) => $q->whereIn('branch_id', $allowed))
            ->when($this->search, function ($q) {
                $s = trim($this->search);

                $q->where(function ($qq) use ($s) {
                    $qq->where('employee_no', 'like', "%{$s}%")
                        ->orWhere('name_ar', 'like', "%{$s}%")
                        ->orWhere('name_en', 'like', "%{$s}%")
                        ->orWhere('email_work', 'like', "%{$s}%")
                        ->orWhere('mobile', 'like', "%{$s}%")
                        ->orWhere('national_id', 'like', "%{$s}%");
                });
            })
           ->when($this->departmentId !== 'all', fn ($q) => $q->where('department_id', (int) $this->departmentId))
            ->when($this->jobTitleId !== 'all', fn ($q) => $q->where('job_title_id', (int) $this->jobTitleId))

            ->when($this->branchFilterId !== 'all', fn ($q) => $q->where('branch_id', (int) $this->branchFilterId))

            ->when($this->contractType !== 'all', function ($q) {
                $v = strtolower((string) $this->contractType);

                $q->where(function ($qq) use ($v) {
                    $qq->where('contract_type', $v)
                    ->orWhere('contract_type', strtoupper($v));
                });
            })

            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->when($this->hiringDateType !== 'all', function ($q) {
                if ($this->hiringDateStart) {
                    $q->whereDate('hired_at', '>=', $this->hiringDateStart);
                }
                if ($this->hiringDateEnd) {
                    $q->whereDate('hired_at', '<=', $this->hiringDateEnd);
                }
            })
            ->with(['department', 'jobTitle', 'documents'])
            ->orderByDesc('id')
            ->paginate($this->perPage);
            $branchesMap = $this->loadBranchesMap($employees, $companyId);

       return view('employees::livewire.employees.index', [
            'employees'          => $employees,
            'departmentsOptions' => $departmentsOptions,
            'jobTitlesOptions'   => $jobTitlesOptions,
            'branchesOptions'    => $branchesOptions,
            'branchesMap'        => $branchesMap,
        ])->layout('layouts.company-admin');
            
    }

    // --- Actions ---

    public function openDeactivateModal($employeeId)
    {
        $companyId = $this->getCompanyId();

        $allowed = DB::table('branch_user_access')
            ->where('user_id', Auth::id())
            ->where('saas_company_id', $companyId)
            ->pluck('branch_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $this->selectedEmployee = Employee::query()
            ->where('saas_company_id', $companyId)
            ->when(! empty($allowed), fn ($q) => $q->whereIn('branch_id', $allowed))
            ->findOrFail($employeeId);
        $this->deactivateReason = '';
        $this->deactivateDate = now()->format('Y-m-d');
        $this->deactivateNotes = '';
        $this->showDeactivateModal = true;
    }
 
    public function closeDeactivateModal()
    {
        $this->showDeactivateModal = false;
        $this->selectedEmployee = null;
    }
 
    public function deactivateEmployee()
    {
        $this->validate([
            'deactivateReason' => 'required|string',
            'deactivateDate'   => 'required|date',
        ]);
 
        if ($this->selectedEmployee) {
            $this->selectedEmployee->update([
                'status'   => 'SUSPENDED',
                'ended_at' => $this->deactivateDate,
            ]);
        }
 
        $this->closeDeactivateModal();
    }
 
    public function activateEmployee($employeeId)
    {
        $companyId = $this->getCompanyId();

        $allowed = DB::table('branch_user_access')
            ->where('user_id', Auth::id())
            ->where('saas_company_id', $companyId)
            ->pluck('branch_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $employee = Employee::query()
            ->where('saas_company_id', $companyId)
            ->when(! empty($allowed), fn ($q) => $q->whereIn('branch_id', $allowed))
            ->findOrFail($employeeId);
            $employee->update([
            'status'   => 'ACTIVE',
            'ended_at' => null,
        ]);
    }
 
    // --- Termination / Offboarding Logic ---
 
    public function openTerminationModal($employeeId)
    {
        $companyId = $this->getCompanyId();

        $user = Auth::user();
        $allowed = null;

        if ($user && method_exists($user, 'restrictedBranchIds')) {
            $allowed = $user->restrictedBranchIds(); // null | [] | [ids]
        } else {
            $tmp = DB::table('branch_user_access')
                ->where('user_id', Auth::id())
                ->where('saas_company_id', $companyId)
                ->pluck('branch_id')
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            $allowed = ! empty($tmp) ? $tmp : null;
        }

        if (is_array($allowed) && empty($allowed)) {
            abort(404);
        }

        $this->selectedEmployee = Employee::query()
            ->where('saas_company_id', $companyId)
            ->when(is_array($allowed), fn ($q) => $q->whereIn('branch_id', $allowed))
            ->findOrFail($employeeId);

        $this->terminationType = '';
        $this->terminationDate = now()->format('Y-m-d');
        $this->terminationReason = '';
        $this->dueSalary = 0;
        $this->dueVacation = 0;
        $this->dueOthers = 0;

        $this->showTerminationModal = true;
    }
 
    public function closeTerminationModal()
    {
        $this->showTerminationModal = false;
        $this->selectedEmployee = null;
    }
 
    public function terminateEmployee()
    {
        $this->validate([
            'terminationType'   => 'required|string',
            'terminationDate'   => 'required|date',
            'terminationReason' => 'nullable|string',
            'dueSalary'         => 'nullable|numeric|min:0',
            'dueVacation'       => 'nullable|numeric|min:0',
            'dueOthers'         => 'nullable|numeric|min:0',
        ]);
 
        if ($this->selectedEmployee) {
            $this->selectedEmployee->update([
                'status'   => 'TERMINATED',
                'ended_at' => $this->terminationDate,
            ]);
        }
 
        $this->closeTerminationModal();
    }
 
    // --- Import Actions ---
 
    public function openImportModal()
    {
        $this->importFile = null;
        $this->importValidationErrors = [];
        $this->showImportModal = true;
    }
 
    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->importFile = null;
        $this->importValidationErrors = [];
    }
 
    public function downloadTemplate()
    {
        $filename = 'employee_import_full_template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
 
        $callback = function () {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
 
            fputcsv($file, [
                tr('Employee No'),           // 0 (Optional)
                tr('Name AR'),              // 1
                tr('Name EN'),              // 2
                tr('National ID'),          // 3
                tr('National ID Expiry'),   // 4
                tr('Nationality'),          // 5
                tr('Birth Date'),           // 6
                tr('Gender'),               // 7
                tr('Marital Status'),       // 8
                tr('Children Count'),       // 9
                tr('Mobile'),               // 10
                tr('Email Work'),           // 11
                tr('Email Personal'),       // 12
                tr('Main Department Code'),      // 13
                tr('Sub Department Code'),       // 14
                tr('Job Title Code'),       // 15
                tr('Manager Employee No'),  // 16
                tr('Hired At'),             // 17
                tr('Basic Salary'),         // 18
                tr('Allowances'),           // 19
                tr('Annual Leave Days'),    // 20
                tr('Contract Type'),        // 21
                tr('Contract Duration (Months)'), // 22
                tr('City'),                 // 23
                tr('District'),             // 24
                tr('Address'),              // 25
                tr('Emergency Contact Name'), // 26
                tr('Emergency Contact Phone'), // 27
                tr('Emergency Relation'),    // 28
            ]);
 
            // Sample Row (Empty employee_no)
            fputcsv($file, [
                '', 'أحمد محمد', 'Ahmed Mohamed', '1234567890', '2030-01-01', 'Saudi', '1990-05-15', 'MALE', 'MARRIED', '2',
                '0500000000', 'ahmed@company.com', 'ahmed@personal.com', 'DEPT-001', 'SUB-001', 'JOB-001', '', '2024-01-01',
                '8000', '2000', '30', 'LIMITED', '24', 'Riyadh', 'Al-Malqa', 'King Saud St', 'Ali Mohamed', '0511111111', 'Brother'
            ]);
 
            fclose($file);
        };
 
        return response()->stream($callback, 200, $headers);
    }
 
    public function downloadDepartmentsCodes()
    {
        $companyId = $this->getCompanyId();
        $Department = $this->departmentModelClass();
        $departments = $Department::forCompany($companyId)->get(['name', 'code']);
 
        $filename = 'departments_codes.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
 
        $callback = function () use ($departments) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
            fputcsv($file, [tr('Department Name'), tr('Department Code')]);
            foreach ($departments as $dept) {
                fputcsv($file, [$dept->name, $dept->code ?? 'N/A']);
            }
            fclose($file);
        };
 
        return response()->stream($callback, 200, $headers);
    }
 
    public function downloadJobTitlesCodes()
    {
        $companyId = $this->getCompanyId();
        $JobTitle = $this->jobTitleModelClass();
        $jobTitles = $JobTitle::forCompany($companyId)->get(['name', 'code']);
 
        $filename = 'job_titles_codes.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
 
        $callback = function () use ($jobTitles) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
            fputcsv($file, [tr('Job Title Name'), tr('Job Title Code')]);
            foreach ($jobTitles as $job) {
                fputcsv($file, [$job->name, $job->code ?? 'N/A']);
            }
            fclose($file);
        };
 
        return response()->stream($callback, 200, $headers);
    }
 
    public function import()
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:4096',
        ]);

        $this->isImporting = true;
        $this->importValidationErrors = [];
        $companyId = $this->getCompanyId();
        
        $path = $this->importFile->getRealPath();
        $file = fopen($path, 'r');
        
        // Detect Delimiter
        $firstLine = fgets($file);
        $delimiter = (str_contains($firstLine, ';') && !str_contains($firstLine, ',')) ? ';' : ',';
        rewind($file);
        
        // Skip header
        fgets($file);

        $rowCount = 0;
        $importedCount = 0;
        
        $DepartmentModel = $this->departmentModelClass();
        $JobTitleModel = $this->jobTitleModelClass();

        try {
            while (($data = fgetcsv($file, 0, $delimiter)) !== FALSE) {
                $rowCount++;
                // Skip empty or malformed rows
                if (count($data) < 4 || empty(array_filter($data))) continue;

                // Clean data helper (handles Scientific Notation from Excel)
                $clean = function($val) {
                    $val = trim($val ?? '');
                    if (empty($val)) return null;
                    // Fix scientific notation e.g., 5.5E+07 -> 55000000
                    if (stripos($val, 'E+') !== false && is_numeric($val)) {
                        return number_format((float)$val, 0, '.', '');
                    }
                    return $val;
                };

                // Map columns
                $row = [
                    'employee_no'              => $clean($data[0] ?? ''),
                    'name_ar'                  => $clean($data[1] ?? ''),
                    'name_en'                  => $clean($data[2] ?? ''),
                    'national_id'              => $clean($data[3] ?? ''),
                    'national_id_expiry'       => $clean($data[4] ?? ''),
                    'nationality'              => $clean($data[5] ?? ''),
                    'birth_date'               => $clean($data[6] ?? ''),
                    'gender'                   => strtoupper($clean($data[7] ?? '')),
                    'marital_status'           => strtoupper($clean($data[8] ?? '')),
                    'children_count'           => (int) $clean($data[9] ?? 0),
                    'mobile'                   => $clean($data[10] ?? ''),
                    'email_work'               => $clean($data[11] ?? ''),
                    'email_personal'           => $clean($data[12] ?? ''),
                    'dept_code'                => $clean($data[13] ?? ''),
                    'sub_dept_code'            => $clean($data[14] ?? ''),
                    'job_code'                 => $clean($data[15] ?? ''),
                    'manager_emp_no'           => $clean($data[16] ?? ''),
                    'hired_at'                 => $clean($data[17] ?? ''),
                    'basic_salary'             => (float) $clean($data[18] ?? 0),
                    'allowances'               => (float) $clean($data[19] ?? 0),
                    'annual_leave_days'        => (int) $clean($data[20] ?? 30),
                    'contract_type'            => $clean($data[21] ?? ''),
                    'contract_duration_months' => (int) $clean($data[22] ?? 0),
                    'city'                     => $clean($data[23] ?? ''),
                    'district'                 => $clean($data[24] ?? ''),
                    'address'                  => $clean($data[25] ?? ''),
                    'emergency_contact_name'   => $clean($data[26] ?? ''),
                    'emergency_contact_phone'  => $clean($data[27] ?? ''),
                    'emergency_contact_relation' => $clean($data[28] ?? ''),
                ];

                // Basic Mandatory Validation
                if (empty($row['name_ar']) || empty($row['national_id'])) {
                    $this->importValidationErrors[] = $this->trp('Row :row: Name AR and National ID are required.', ['row' => $rowCount]);
                    continue;
                }

                // Duplicate Check (More granular results)
                $duplicate = Employee::where('saas_company_id', $companyId)
                    ->where(function($q) use ($row) {
                        $q->where('national_id', $row['national_id'])
                          ->when($row['employee_no'], fn($qq) => $qq->orWhere('employee_no', $row['employee_no']))
                          ->when($row['email_work'], fn($qq) => $qq->orWhere('email_work', $row['email_work']));
                    })->first();

                if ($duplicate) {
                    $field = 'ID';
                    if ($row['employee_no'] && $duplicate->employee_no == $row['employee_no']) $field = tr('Employee No');
                    if ($row['email_work'] && $duplicate->email_work == $row['email_work']) $field = tr('Email');
                    
                    $this->importValidationErrors[] = $this->trp('Row :row: Employee already exists matching :field (:value).', [
                        'row' => $rowCount, 
                        'field' => $field,
                        'value' => ($field == tr('Employee No') ? $row['employee_no'] : ($field == tr('Email') ? $row['email_work'] : $row['national_id']))
                    ]);
                    continue;
                }

                // Find Department
                $deptId = null;
                if (!empty($row['dept_code'])) {
                    $dept = $DepartmentModel::where('saas_company_id', $companyId)->where('code', $row['dept_code'])->first();
                    if ($dept) $deptId = $dept->id;
                    else $this->importValidationErrors[] = $this->trp('Row :row: Main Department code ":code" not found.', ['row' => $rowCount, 'code' => $row['dept_code']]);
                }

                // Find Sub Department
                $subDeptId = null;
                if (!empty($row['sub_dept_code'])) {
                    $subDept = $DepartmentModel::where('saas_company_id', $companyId)->where('code', $row['sub_dept_code'])->first();
                    if ($subDept) $subDeptId = $subDept->id;
                    else $this->importValidationErrors[] = $this->trp('Row :row: Sub Department code ":code" not found.', ['row' => $rowCount, 'code' => $row['sub_dept_code']]);
                }

                // Find Job Title
                $jobId = null;
                if (!empty($row['job_code'])) {
                    $job = $JobTitleModel::where('saas_company_id', $companyId)->where('code', $row['job_code'])->first();
                    if ($job) $jobId = $job->id;
                    else $this->importValidationErrors[] = $this->trp('Row :row: Job code ":code" not found.', ['row' => $rowCount, 'code' => $row['job_code']]);
                }

                // Find Manager
                $managerId = null;
                if (!empty($row['manager_emp_no'])) {
                    $manager = Employee::where('saas_company_id', $companyId)->where('employee_no', $row['manager_emp_no'])->first();
                    if ($manager) $managerId = $manager->id;
                    else $this->importValidationErrors[] = $this->trp('Row :row: Manager ":no" not found.', ['row' => $rowCount, 'no' => $row['manager_emp_no']]);
                }

                // Fuzzy Matching for Gender and Marital Status
                $gender = 'MALE';
                if ($row['gender'] && str_starts_with($row['gender'], 'F')) $gender = 'FEMALE';
                
                $mStatus = 'SINGLE';
                if ($row['marital_status'] && str_starts_with($row['marital_status'], 'M')) $mStatus = 'MARRIED';

                try {
                    Employee::create([
                        'saas_company_id'          => $companyId,
                        'employee_no'              => $row['employee_no'] ?: null,
                        'name_ar'                  => $row['name_ar'],
                        'name_en'                  => $row['name_en'],
                        'national_id'              => $row['national_id'],
                        'national_id_expiry'       => (empty($row['national_id_expiry']) || str_contains($row['national_id_expiry'], '#')) ? now()->addYear() : $row['national_id_expiry'],
                        'nationality'              => $row['nationality'],
                        'birth_date'               => (empty($row['birth_date']) || str_contains($row['birth_date'], '#')) ? '1990-01-01' : $row['birth_date'],
                        'gender'                   => $gender,
                        'marital_status'           => $mStatus,
                        'children_count'           => $row['children_count'] ?: 0,
                        'mobile'                   => $row['mobile'],
                        'email_work'               => $row['email_work'] ?: null,
                        'email_personal'           => $row['email_personal'] ?: null,
                        'department_id'            => $deptId,
                        'sub_department_id'        => $subDeptId,
                        'job_title_id'             => $jobId,
                        'manager_id'               => $managerId,
                        'sector'                   => tr('Main'),
                        'grade'                    => 'Staff',
                        'job_function'             => $JobTitleModel::find($jobId)?->name ?? 'N/A',
                        'hired_at'                 => (empty($row['hired_at']) || str_contains($row['hired_at'], '#')) ? now() : $row['hired_at'],
                        'basic_salary'             => $row['basic_salary'] ?: 0,
                        'allowances'               => $row['allowances'] ?: 0,
                        'annual_leave_days'        => $row['annual_leave_days'] ?: 30,
                        'contract_type'            => $row['contract_type'] ?: tr('PERMANENT'),
                        'contract_duration_months' => $row['contract_duration_months'] ?: 0,
                        'city'                     => $row['city'] ?: tr('Unknown'),
                        'district'                 => $row['district'],
                        'address'                  => $row['address'],
                        'emergency_contact_name'   => $row['emergency_contact_name'] ?: 'N/A',
                        'emergency_contact_phone'  => $row['emergency_contact_phone'] ?: '000',
                        'emergency_contact_relation' => $row['emergency_contact_relation'] ?: 'N/A',
                        'status'                   => 'ACTIVE',
                    ]);
                    $importedCount++;
                } catch (\Exception $e) {
                    // Try to extract a more human-readable error from database exceptions
                    $error = $e->getMessage();
                    
                    // Simple logging for debugging if needed
                    \Log::error('Employee Import Error Row ' . $rowCount . ': ' . $error);

                    if (str_contains($error, 'Data too long')) $error = tr('Some data fields are too long for the database.');
                    elseif (str_contains($error, 'Incorrect date value')) $error = tr('Invalid date format in one of the columns.');
                    elseif (str_contains($error, 'doesn\'t have a default value')) {
                        preg_match("/Field '(.+)' doesn't have a default value/", $error, $matches);
                        $field = $matches[1] ?? 'unknown';
                        $error = tr('The following required field is missing: ') . $field;
                    }
                    
                    $this->importValidationErrors[] = $this->trp('Row :row: Failed to save: :error', ['row' => $rowCount, 'error' => $error]);
                }
            }
        } catch (\Throwable $th) {
            $this->importValidationErrors[] = tr('Critical error during import: ') . $th->getMessage();
        } finally {
            fclose($file);
            $this->isImporting = false;
        }

        if ($importedCount > 0) {
            $this->dispatch('toast',
                type: 'success',
                title: tr('Import Successful'),
                message: $this->trp(':count employees imported.', ['count' => $importedCount])
            );
        }

        if (empty($this->importValidationErrors)) {
            $this->closeImportModal();
        }
    }

    private function branchModelClass(): ?string
{
    $candidates = [
        \App\Models\Branch::class,
        \Athka\SystemSettings\Models\Branch::class,
        \Athka\Saas\Models\Branch::class,
    ];

    foreach ($candidates as $class) {
        if (class_exists($class)) {
            return $class;
        }
    }

    return null;
}

private function loadBranchesMap($employees, int $companyId): array
{
    $Branch = $this->branchModelClass();

    if (! $Branch) {
        return [];
    }

    $collection = method_exists($employees, 'getCollection')
        ? $employees->getCollection()
        : collect($employees);

    $branchIds = $collection->pluck('branch_id')->filter()->unique()->values();

    if ($branchIds->isEmpty()) {
        return [];
    }

    $query = $Branch::query()->whereIn('id', $branchIds->all());

    //   لو جدول الفروع فيه company_id أو saas_company_id نفلتر حسب الشركة (اختياري وآمن)
    try {
        $table = (new $Branch)->getTable();

        if (Schema::hasColumn($table, 'saas_company_id')) {
            $query->where('saas_company_id', $companyId);
        } elseif (Schema::hasColumn($table, 'company_id')) {
            $query->where('company_id', $companyId);
        }

        //   نختار أعمدة موجودة فقط
        $cols = ['id'];

        foreach (['name', 'name_ar', 'name_en', 'code'] as $c) {
            if (Schema::hasColumn($table, $c)) {
                $cols[] = $c;
            }
        }

        $branches = $query->get($cols);
    } catch (\Throwable $e) {
        // fallback: خذ كل الأعمدة إذا فشل فحص الأعمدة
        $branches = $query->get();
    }

    return $branches->mapWithKeys(function ($b) {
        return [
            (int) $b->id => [
                'name'    => $b->name    ?? null,
                'name_ar' => $b->name_ar ?? null,
                'name_en' => $b->name_en ?? null,
                'code'    => $b->code    ?? null,
            ],
        ];
    })->toArray();
}

}




