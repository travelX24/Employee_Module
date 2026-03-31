<?php

namespace Athka\Employees\Livewire\Employees;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\ExcelExportService;
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
    
    public function mount()
    {
        // One of these permissions is required to see the listing
        if (!Auth::user()->can('employees.view')) {
            abort(403);
        }
    }

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

private function isEmployeeLockedStatus(?string $status): bool
{
    return in_array((string) $status, [
        'SUSPENDED',
        'TERMINATED',
        'ARCHIVED',
        'ENDED',
    ], true);
}

private function blockLockedEmployeeAction(): void
{
    $this->dispatch('toast',
        type: 'warning',
        title: tr('Action not allowed'),
        message: tr('This employee can only be viewed after final suspension or termination.')
    );
}

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
            ->when(! empty($allowed), fn ($q) => $q->whereIn('branch_id', $allowed))
            // ✅ NEW: Scoping based on permission
            ->when(!Auth::user()->can('employees.view.all'), function ($q) {
                $user = Auth::user();
                $q->where(function ($qq) use ($user) {
                    if ($user->employee_id) {
                        $qq->where('manager_id', $user->employee_id);
                    }
                    if ($user->department_id) {
                        $qq->orWhere('department_id', $user->department_id);
                    }
                    if (!$user->employee_id && !$user->department_id) {
                        $qq->where('id', 0);
                    }
                });
            });        
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
            return $this->exportToExcel($employees, $fieldsToExport, app(ExcelExportService::class));
        } else {
            return $this->exportToPdf($employees, $fieldsToExport);
        }
    }

    private function exportToExcel($employees, $fields, ExcelExportService $exporter)
    {
        $filename = 'employees_export_' . date('Y-m-d_H-i-s');
        $available = $this->availableFields;
        
        $headers = [];
        foreach ($fields as $field) {
            $headers[] = $available[$field] ?? $field;
        }

        $data = $employees->map(function ($employee) use ($fields) {
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
                $row[] = (string) $value;
            }
            return $row;
        })->toArray();

        $this->showExportModal = false;
        return $exporter->export($filename, $headers, $data);
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
            // ✅ NEW: Scoping based on permission
            ->when(!Auth::user()->can('employees.view.all'), function ($q) {
                $user = Auth::user();
                $q->where(function ($qq) use ($user) {
                    if ($user->employee_id) {
                        $qq->where('manager_id', $user->employee_id);
                    }
                    if ($user->department_id) {
                        $qq->orWhere('department_id', $user->department_id);
                    }
                    
                    // If no employee ID and no department assigned, show nothing unless they have higher permission
                    if (!$user->employee_id && !$user->department_id) {
                        $qq->where('id', 0);
                    }
                });
            })
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
    $this->authorize('employees.status.manage');

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
        ->when(!Auth::user()->can('employees.view'), function ($q) {
            $user = Auth::user();
            $q->where(function ($qq) use ($user) {
                if ($user->employee_id) $qq->where('manager_id', $user->employee_id);
                if ($user->department_id) $qq->orWhere('department_id', $user->department_id);
            });
        })
        ->findOrFail($employeeId);

    if ($this->isEmployeeLockedStatus($this->selectedEmployee->status)) {
        $this->selectedEmployee = null;
        $this->blockLockedEmployeeAction();
        return;
    }

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

    if (! $this->selectedEmployee) {
        return;
    }

    $this->selectedEmployee->refresh();

    if ($this->isEmployeeLockedStatus($this->selectedEmployee->status)) {
        $this->closeDeactivateModal();
        $this->blockLockedEmployeeAction();
        return;
    }

    $this->selectedEmployee->update([
        'status'   => 'SUSPENDED',
        'ended_at' => $this->deactivateDate,
    ]);

    $this->closeDeactivateModal();
}
 
    public function activateEmployee($employeeId)
{
    $this->authorize('employees.status.manage');

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
        ->when(!Auth::user()->can('employees.view.all'), function ($q) {
            $user = Auth::user();
            $q->where(function ($qq) use ($user) {
                if ($user->employee_id) $qq->where('manager_id', $user->employee_id);
                if ($user->department_id) $qq->orWhere('department_id', $user->department_id);
            });
        })
        ->findOrFail($employeeId);

    if ($this->isEmployeeLockedStatus($employee->status)) {
        $this->blockLockedEmployeeAction();
        return;
    }

    $employee->update([
        'status'   => 'ACTIVE',
        'ended_at' => null,
    ]);
}
 
    // --- Termination / Offboarding Logic ---
 
    public function openTerminationModal($employeeId)
{
    $this->authorize('employees.delete');

    $companyId = $this->getCompanyId();

    $user = Auth::user();
    $allowed = null;

    if ($user && method_exists($user, 'restrictedBranchIds')) {
        $allowed = $user->restrictedBranchIds();
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
        ->when(!Auth::user()->can('employees.view.all'), function ($q) {
            $user = Auth::user();
            $q->where(function ($qq) use ($user) {
                if ($user->employee_id) $qq->where('manager_id', $user->employee_id);
                if ($user->department_id) $qq->orWhere('department_id', $user->department_id);
            });
        })
        ->findOrFail($employeeId);

    if ($this->isEmployeeLockedStatus($this->selectedEmployee->status)) {
        $this->selectedEmployee = null;
        $this->blockLockedEmployeeAction();
        return;
    }

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
    $this->authorize('employees.delete');

    $this->validate([
        'terminationType'   => 'required|string',
        'terminationDate'   => 'required|date',
        'terminationReason' => 'nullable|string',
        'dueSalary'         => 'nullable|numeric|min:0',
        'dueVacation'       => 'nullable|numeric|min:0',
        'dueOthers'         => 'nullable|numeric|min:0',
    ]);

    if (! $this->selectedEmployee) {
        return;
    }

    $this->selectedEmployee->refresh();

    if ($this->isEmployeeLockedStatus($this->selectedEmployee->status)) {
        $this->closeTerminationModal();
        $this->blockLockedEmployeeAction();
        return;
    }

    $this->selectedEmployee->update([
        'status'   => 'TERMINATED',
        'ended_at' => $this->terminationDate,
    ]);

    $this->closeTerminationModal();
}
 
    // --- Import Actions ---
 
    public function openImportModal()
    {
        $this->authorize('employees.import');
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
        $filename = 'employee_import_full_template.xlsx';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Employees');

        // Headers
        $headings = [
            tr('Name AR'),                    // A
            tr('Name EN'),                    // B
            tr('National ID'),                // C
            tr('National ID Expiry'),         // D
            tr('Nationality'),                // E
            tr('Birth Date'),                 // F
            tr('Gender'),                     // G
            tr('Marital Status'),             // H
            tr('Children Count'),             // I
            tr('Mobile'),                     // J
            tr('Email Work'),                 // K
            tr('Email Personal'),             // L
            tr('Main Department Code'),       // M
            tr('Sub Department Code'),        // N
            tr('Job Title Code'),             // O
            tr('Manager Employee No'),        // P
            tr('Hired At'),                   // Q
            tr('Basic Salary'),               // R
            tr('Allowances'),                 // S
            tr('Annual Leave Days'),          // T
            tr('Contract Type'),              // U
            tr('Contract Duration (Months)'), // V
            tr('City'),                       // W
            tr('District'),                   // X
            tr('Address'),                    // Y
            tr('Emergency Contact Name'),     // Z
            tr('Emergency Contact Phone'),    // AA
            tr('Emergency Relation'),         // AB
        ];

        // Ensure headers are written
        $col = 'A';
        foreach ($headings as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }

        // sample row
        $sampleData = [
            'أحمد محمد', 'Ahmed Mohamed', '1234567890', '2030-01-01', 'Saudi Arabia', '1990-05-15', 'male', 'married', '2',
            '0500000000', 'ahmed@company.com', 'ahmed@personal.com', 'DEPT-001', 'SUB-001', 'JOB-001', '', '2024-01-01',
            '8000', '2000', '30', 'permanent', '', 'Riyadh', 'Al-Malqa', 'King Saud St', 'Ali Mohamed', '0511111111', 'Brother'
        ];
        $col = 'A';
        foreach ($sampleData as $dataItem) {
            $sheet->setCellValue($col . '2', $dataItem);
            $col++;
        }

        // --- Data Sheet for Validation ---
        $dataSheet = $spreadsheet->createSheet();
        $dataSheet->setTitle('DataStorage_Hidden');
        
        $companyId = $this->getCompanyId();

        // Departments
        $Department = $this->departmentModelClass();
        $allDepts = $Department::forCompany($companyId)->get(['id', 'code', 'name', 'parent_id']);
        
        $mainDepts = $allDepts->whereNull('parent_id');
        $subDepts = $allDepts->whereNotNull('parent_id');

        $deptCodes = $mainDepts->map(fn($d) => $d->code ? ($d->name . ' (' . $d->code . ')') : $d->name)->filter()->values()->toArray();
        $subDeptCodes = $subDepts->map(fn($d) => $d->code ? ($d->name . ' (' . $d->code . ')') : $d->name)->filter()->values()->toArray();

        // Job Titles
        $JobTitle = $this->jobTitleModelClass();
        $jobTitles = $JobTitle::forCompany($companyId)->get(['code', 'name']);
        $jobCodes = $jobTitles->map(fn($j) => $j->code ? ($j->name . ' (' . $j->code . ')') : $j->name)->filter()->values()->toArray();

        // Managers
        $employeesList = Employee::where('saas_company_id', $companyId)->get(['employee_no', 'name_ar', 'name_en']);
        $managerCodes = $employeesList->map(function($e) {
            $name = $e->name_ar ?: $e->name_en;
            return $e->employee_no ? ($name . ' (' . $e->employee_no . ')') : $name;
        })->filter()->values()->toArray();

        // Static Lists
        $isAr = app()->getLocale() === 'ar';
        $genderList = $isAr ? ['ذكر', 'أنثى'] : ['Male', 'Female'];
        $maritalList = $isAr ? ['أعزب', 'متزوج', 'مطلق', 'أرمل'] : ['Single', 'Married', 'Divorced', 'Widowed'];
        $contractList = $isAr ? ['دائم', 'مؤقت', 'تجربة', 'مقاول'] : ['Permanent', 'Temporary', 'Probation', 'Contractor'];
        $relationList = $isAr ? ['أب', 'أم', 'أخ', 'أخت', 'زوج', 'زوجة', 'ابن', 'ابنة', 'صديق', 'أخرى'] 
                             : ['Father', 'Mother', 'Brother', 'Sister', 'Husband', 'Wife', 'Son', 'Daughter', 'Friend', 'Other'];
        
        $nationalityList = [
            'Saudi Arabia', 'Egypt', 'Jordan', 'India', 'Pakistan', 'Philippines', 'Suriya', 'Lebanon', 'Yemen', 'Sudan',
            'United Arab Emirates', 'Kuwait', 'Oman', 'Bahrain', 'Qatar', 'Morocco', 'Algeria', 'Tunisia', 'Libya'
        ];
        if ($isAr) {
            $nationalityList = [
                'المملكة العربية السعودية', 'مصر', 'الأردن', 'الهند', 'باكستان', 'الفلبين', 'سوريا', 'لبنان', 'اليمن', 'السودان',
                'الإمارات العربية المتحدة', 'الكويت', 'عمان', 'البحرين', 'قطر', 'المغرب', 'الجزائر', 'تونس', 'ليبيا'
            ];
        }

        $writeCol = function ($colLetter, $list) use ($dataSheet) {
            $row = 1;
            foreach ($list as $val) {
                $val = str_replace([',', '"'], '', (string)$val);
                $dataSheet->setCellValue($colLetter . $row, $val);
                $row++;
            }
            return $row - 1; // max row
        };

        $maxDeptRow = $writeCol('A', $deptCodes);
        $maxJobRow = $writeCol('B', $jobCodes);
        $maxManagerRow = $writeCol('C', $managerCodes);
        $maxGenderRow = $writeCol('D', $genderList);
        $maxMaritalRow = $writeCol('E', $maritalList);
        $maxContractRow = $writeCol('F', $contractList);
        $maxNationRow = $writeCol('G', $nationalityList);
        $maxSubDeptRow = $writeCol('H', $subDeptCodes);
        $maxRelationRow = $writeCol('I', $relationList);

        // Hide data sheet
        $dataSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

        // --- Apply Validation to the Main Sheet ---
        $applyDropdown = function ($colLetter, $dataColLetter, $maxDataRow) use ($sheet) {
            if ($maxDataRow < 1) return; // No data to validate against
            
            for ($row = 2; $row <= 1000; $row++) {
                $validation = $sheet->getCell($colLetter . $row)->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                           ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                           ->setAllowBlank(true)
                           ->setShowInputMessage(true)
                           ->setShowErrorMessage(true)
                           ->setShowDropDown(true)
                           ->setErrorTitle(tr('Input error'))
                           ->setError(tr('Value is not in list.'))
                           ->setPromptTitle(tr('Pick from list'))
                           ->setPrompt(tr('Please select a value from the drop-down list.'))
                           ->setFormula1('DataStorage_Hidden!$' . $dataColLetter . '$1:$' . $dataColLetter . '$' . $maxDataRow);
            }
        };

        $applyDropdown('E', 'G', $maxNationRow);   // Nationality
        $applyDropdown('G', 'D', $maxGenderRow);   // Gender
        $applyDropdown('H', 'E', $maxMaritalRow);  // Marital status
        $applyDropdown('M', 'A', $maxDeptRow);     // Main Dept
        $applyDropdown('N', 'H', $maxSubDeptRow);  // Sub Dept
        $applyDropdown('O', 'B', $maxJobRow);      // Job Title
        $applyDropdown('P', 'C', $maxManagerRow);  // Manager
        $applyDropdown('U', 'F', $maxContractRow); // Contract type
        $applyDropdown('AB', 'I', $maxRelationRow); // Emergency Relation

        $spreadsheet->setActiveSheetIndex(0);

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

        $headersHttp = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ];

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, $headersHttp);
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
        $this->authorize('employees.import');

        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ], [
            'importFile.required' => $this->trp('Please select a file to import.', [], 'ui'),
            'importFile.mimes'    => $this->trp('The file must be in CSV or Excel format (.csv, .xlsx, .xls).', [], 'ui'),
            'importFile.max'      => $this->trp('The file size must not exceed 5MB.', [], 'ui'),
        ]);

        $this->isImporting = true;
        $this->importValidationErrors = [];
        $companyId = $this->getCompanyId();

        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        try {
            // --- Pre-fetch Data for Optimization ---
            $DepartmentModel = $this->departmentModelClass();
            $JobTitleModel = $this->jobTitleModelClass();

            // Load Departments Map
            $allDepts = $DepartmentModel::where('saas_company_id', $companyId)->get(['id', 'code', 'name']);
            $deptMap = [];
            foreach ($allDepts as $d) {
                if ($d->code) $deptMap[strtolower(trim($d->code))] = $d->id;
                $deptMap[strtolower(trim($d->name))] = $d->id;
            }

            // Load Job Titles Map
            $allJobTitles = $JobTitleModel::where('saas_company_id', $companyId)->get(['id', 'code', 'name']);
            $jobMap = [];
            foreach ($allJobTitles as $jt) {
                if ($jt->code) $jobMap[strtolower(trim($jt->code))] = $jt->id;
                $jobMap[strtolower(trim($jt->name))] = $jt->id;
            }

            // Load Managers Map
            $allEmployees = Employee::where('saas_company_id', $companyId)->get(['id', 'employee_no', 'name_ar', 'name_en']);
            $managerMap = [];
            $existingDataMap = [
                'national_ids' => [],
                'emails' => [],
                'mobiles' => [],
            ];
            foreach ($allEmployees as $e) {
                if ($e->employee_no) $managerMap[strtolower(trim($e->employee_no))] = $e->id;
                $managerMap[strtolower(trim($e->name_ar))] = $e->id;
                $managerMap[strtolower(trim($e->name_en))] = $e->id;
               
                // Duplicate detection maps
                $existingDataMap['national_ids'][$e->national_id] = $e->id;
                if ($e->email_work) $existingDataMap['emails'][strtolower($e->email_work)] = $e->id;
                if ($e->mobile) $existingDataMap['mobiles'][$e->mobile] = $e->id;
            }

            $forcedDeptId = null;
            $forcedManagerId = null;
            if (!Auth::user()->can('employees.view.all')) {
                $forcedDeptId = Auth::user()->department_id;
                $forcedManagerId = Auth::user()->employee_id;
            }

            $defaultBranchId = (int) (Auth::user()?->branch_id ?? 0) ?: null;
            $defaultAnnualLeaveDays = 21;
            if (class_exists(\Athka\Saas\Models\SaasCompanyOtherinfo::class)) {
                $defaultAnnualLeaveDays = (int) (\Athka\Saas\Models\SaasCompanyOtherinfo::where('company_id', $companyId)->value('default_annual_leave_days') ?? 21);
            }

            $path = $this->importFile->getRealPath();
            $extension = strtolower($this->importFile->getClientOriginalExtension());

            $rowCount = 0;
            $importedCount = 0;

            // ─────────────────────────────────────────
            // Helper functions
            // ─────────────────────────────────────────
            $clean = function($val) {
                if ($val === null) return null;
                $val = str_replace("\0", "", (string)$val);
                $val = trim($val);
                if (empty($val)) return null;
                if (stripos((string)$val, 'E+') !== false && is_numeric($val)) {
                    return sprintf('%.0f', (float)$val);
                }
                return $val;
            };

            $parseDate = function($val) {
                if (empty($val)) return null;
                if (is_numeric($val) && $val > 40000 && $val < 100000) {
                    try {
                        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)->format('Y-m-d');
                    } catch (\Exception $e) {}
                }
                try {
                    return \Carbon\Carbon::parse($val)->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            };

            $extractCode = function($val) {
                if (preg_match('/\((.*?)\)$/', trim((string)$val), $matches)) {
                    return trim($matches[1]);
                }
                return trim((string)$val);
            };

            $processRow = function(array $data) use (
                $clean, $parseDate, $extractCode,
                $companyId, $defaultBranchId, $defaultAnnualLeaveDays,
                $forcedDeptId, $forcedManagerId,
                $deptMap, $jobMap, $managerMap, $existingDataMap,
                &$rowCount, &$importedCount
            ) {
                $rowCount++;
                if (count($data) < 4 || empty(array_filter($data, fn($v) => !is_null($v) && (string)$v !== ''))) return;

                $rowRaw = [
                    'name_ar'                    => $clean($data[0] ?? ''),
                    'name_en'                    => $clean($data[1] ?? ''),
                    'national_id'                => $clean($data[2] ?? ''),
                    'national_id_expiry'         => $parseDate($clean($data[3] ?? '')),
                    'nationality'                => $clean($data[4] ?? ''),
                    'birth_date'                 => $parseDate($clean($data[5] ?? '')),
                    'gender_input'               => $clean($data[6] ?? ''),
                    'marital_input'              => $clean($data[7] ?? ''),
                    'children_count'             => (int) ($clean($data[8] ?? 0) ?: 0),
                    'mobile'                     => $clean($data[9] ?? ''),
                    'email_work'                 => $clean($data[10] ?? ''),
                    'email_personal'             => $clean($data[11] ?? ''),
                    'dept_code_raw'              => $clean($data[12] ?? ''),
                    'sub_dept_code_raw'          => $clean($data[13] ?? ''),
                    'job_code_raw'               => $clean($data[14] ?? ''),
                    'manager_raw'                => $clean($data[15] ?? ''),
                    'hired_at'                   => $parseDate($clean($data[16] ?? '')),
                    'basic_salary'               => (float) ($clean($data[17] ?? 0) ?: 0),
                    'allowances'                 => (float) ($clean($data[18] ?? 0) ?: 0),
                    'annual_leave_days'          => (int) ($clean($data[19] ?? $defaultAnnualLeaveDays) ?: $defaultAnnualLeaveDays),
                    'contract_input'             => $clean($data[20] ?? ''),
                    'contract_duration'          => (int) ($clean($data[21] ?? 0) ?: 0),
                    'city'                       => $clean($data[22] ?? ''),
                    'district'                   => $clean($data[23] ?? ''),
                    'address'                    => $clean($data[24] ?? ''),
                    'emergency_name'             => $clean($data[25] ?? ''),
                    'emergency_phone'            => $clean($data[26] ?? ''),
                    'emergency_relation'         => $clean($data[27] ?? ''),
                ];

                // --- Validations ---
                if (empty($rowRaw['name_ar'])) {
                    $this->importValidationErrors[] = $this->trp('Row :row: Name AR is required.', ['row' => $rowCount]);
                    return;
                }
                if (empty($rowRaw['national_id'])) {
                    $this->importValidationErrors[] = $this->trp('Row :row: National ID is required.', ['row' => $rowCount]);
                    return;
                }

                // Check Duplicates
                if (isset($existingDataMap['national_ids'][$rowRaw['national_id']])) {
                    $this->importValidationErrors[] = $this->trp('Row :row: Duplicate National ID (:val).', ['row' => $rowCount, 'val' => $rowRaw['national_id']]);
                    return;
                }
                if ($rowRaw['email_work'] && isset($existingDataMap['emails'][strtolower($rowRaw['email_work'])])) {
                    $this->importValidationErrors[] = $this->trp('Row :row: Email already exists (:val).', ['row' => $rowCount, 'val' => $rowRaw['email_work']]);
                    return;
                }
                if ($rowRaw['mobile'] && isset($existingDataMap['mobiles'][$rowRaw['mobile']])) {
                    $this->importValidationErrors[] = $this->trp('Row :row: Mobile already exists (:val).', ['row' => $rowCount, 'val' => $rowRaw['mobile']]);
                    return;
                }

                // Resolve Maps
                $getMappedId = function($val, $map) use ($extractCode) {
                    if (empty($val)) return null;
                    $code = strtolower($extractCode($val));
                    return $map[$code] ?? null;
                };

                $deptId = $getMappedId($rowRaw['dept_code_raw'], $deptMap);
                $subDeptId = $getMappedId($rowRaw['sub_dept_code_raw'], $deptMap);
                $jobId = $getMappedId($rowRaw['job_code_raw'], $jobMap);
                $managerId = $getMappedId($rowRaw['manager_raw'], $managerMap);

                if (!empty($rowRaw['dept_code_raw']) && !$deptId) {
                    $this->importValidationErrors[] = $this->trp('Row :row: Main Department ":val" not found.', ['row' => $rowCount, 'val' => $rowRaw['dept_code_raw']]);
                }
                if (!empty($rowRaw['sub_dept_code_raw']) && !$subDeptId) {
                    $this->importValidationErrors[] = $this->trp('Row :row: Sub Department ":val" not found.', ['row' => $rowCount, 'val' => $rowRaw['sub_dept_code_raw']]);
                }
                if (!empty($rowRaw['job_code_raw']) && !$jobId) {
                    $this->importValidationErrors[] = $this->trp('Row :row: Job Title ":val" not found.', ['row' => $rowCount, 'val' => $rowRaw['job_code_raw']]);
                    if (empty($this->importValidationErrors)) return; // Stop if errors
                }

                // Field Mappings
                $gender = (in_array(strtolower($rowRaw['gender_input']), ['female', 'f', 'أنثى', 'انثى'], true)) ? 'female' : 'male';
                $mStatus = (in_array(strtolower($rowRaw['marital_input']), ['married', 'm', 'متزوج', 'متزوجة'], true)) ? 'married' : 'single';
                
                $cInput = strtolower($rowRaw['contract_input']);
                $contractType = 'permanent';
                if (in_array($cInput, ['temporary', 'probation', 'contractor', 'مؤقت', 'تجربة', 'مقاول'], true)) {
                    $map = ['مؤقت' => 'temporary', 'تجربة' => 'probation', 'مقاول' => 'contractor'];
                    $contractType = $map[$cInput] ?? $cInput;
                }

                try {
                    Employee::create([
                        'saas_company_id'            => $companyId,
                        'branch_id'                  => $defaultBranchId,
                        'name_ar'                    => $rowRaw['name_ar'],
                        'name_en'                    => $rowRaw['name_en'],
                        'national_id'                => $rowRaw['national_id'],
                        'national_id_expiry'         => $rowRaw['national_id_expiry'] ?: now()->addYear()->format('Y-m-d'),
                        'nationality'                => $rowRaw['nationality'] ?: tr('Unknown'),
                        'birth_date'                 => $rowRaw['birth_date'] ?: '1990-01-01',
                        'birth_place'                => $rowRaw['city'] ?: tr('Unknown'),
                        'gender'                     => $gender,
                        'marital_status'             => $mStatus,
                        'children_count'             => $rowRaw['children_count'],
                        'mobile'                     => $rowRaw['mobile'],
                        'email_work'                 => $rowRaw['email_work'],
                        'email_personal'             => $rowRaw['email_personal'],
                        'department_id'              => $forcedDeptId ?: $deptId,
                        'sub_department_id'          => $subDeptId,
                        'job_title_id'               => $jobId,
                        'manager_id'                 => $forcedManagerId ?: $managerId,
                        'sector'                     => 'Staff',
                        'grade'                      => 1,
                        'job_function'               => 'Staff',
                        'hired_at'                   => $rowRaw['hired_at'] ?: now()->format('Y-m-d'),
                        'basic_salary'               => $rowRaw['basic_salary'],
                        'allowances'                 => $rowRaw['allowances'],
                        'annual_leave_days'          => $rowRaw['annual_leave_days'],
                        'contract_type'              => $contractType,
                        'contract_duration_months'   => $rowRaw['contract_duration'],
                        'city'                       => $rowRaw['city'] ?: tr('Unknown'),
                        'district'                   => $rowRaw['district'] ?: tr('Unknown'),
                        'address'                    => $rowRaw['address'] ?: tr('Unknown'),
                        'emergency_contact_name'     => $rowRaw['emergency_name'] ?: tr('Unknown'),
                        'emergency_contact_phone'    => $rowRaw['emergency_phone'] ?: '0000000000',
                        'emergency_contact_relation' => $rowRaw['emergency_relation'] ?: 'أخرى',
                        'status'                     => 'ACTIVE',
                    ]);
                    $importedCount++;
                } catch (\Exception $e) {
                    $this->importValidationErrors[] = $this->trp('Row :row: Failed to save. Error: :err', ['row' => $rowCount, 'err' => substr($e->getMessage(), 0, 80)]);
                }
            };

            DB::transaction(function() use ($extension, $path, $processRow) {
                if (in_array($extension, ['xlsx', 'xls'])) {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
                    $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
                    array_shift($rows); // Header
                    foreach ($rows as $rowData) {
                        $processRow($rowData);
                    }
                } else {
                    $tempFile = fopen($path, 'r');
                    fgetcsv($tempFile); // Skip header
                    while (($data = fgetcsv($tempFile)) !== FALSE) {
                        $processRow($data);
                    }
                    fclose($tempFile);
                }
            });

        } catch (\Throwable $th) {
            $this->importValidationErrors[] = tr('Critical error: ') . $th->getMessage();
        } finally {
            $this->isImporting = false;
        }

        if ($importedCount > 0) {
            $this->dispatch('toast', type: 'success', title: tr('Success'), message: $this->trp(':count employees imported.', ['count' => $importedCount]));
        }

        if (empty($this->importValidationErrors)) {
            $this->closeImportModal();
        }
    }


    private function branchModelClass(): ?string
    {
        $candidates = [
            'App\Models\Branch',
            'Athka\SystemSettings\Models\Branch',
            'Athka\Saas\Models\Branch',
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

        try {
            $table = (new $Branch)->getTable();

            if (Schema::hasColumn($table, 'saas_company_id')) {
                $query->where('saas_company_id', $companyId);
            } elseif (Schema::hasColumn($table, 'company_id')) {
                $query->where('company_id', $companyId);
            }

            $cols = ['id'];
            foreach (['name', 'name_ar', 'name_en', 'code'] as $c) {
                if (Schema::hasColumn($table, $c)) {
                    $cols[] = $c;
                }
            }
            $branches = $query->get($cols);
        } catch (\Throwable $e) {
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
