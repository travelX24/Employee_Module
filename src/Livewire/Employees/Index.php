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
            // ✅ Simplified scoping: if they can't view all, scope by manager/department
            ->when(!Auth::user()->can('employees.view'), function ($q) {
                $user = Auth::user();
                $q->where(function ($qq) use ($user) {
                    if ($user->employee_id) $qq->where('manager_id', $user->employee_id);
                    if ($user->department_id) $qq->orWhere('department_id', $user->department_id);
                });
            })
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
            // ✅ NEW: Scoping based on permission
            ->when(!Auth::user()->can('employees.view.all'), function ($q) {
                $user = Auth::user();
                $q->where(function ($qq) use ($user) {
                    if ($user->employee_id) $qq->where('manager_id', $user->employee_id);
                    if ($user->department_id) $qq->orWhere('department_id', $user->department_id);
                });
            })
            ->findOrFail($employeeId);
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
            // ✅ NEW: Scoping based on permission
            ->when(!Auth::user()->can('employees.view.all'), function ($q) {
                $user = Auth::user();
                $q->where(function ($qq) use ($user) {
                    if ($user->employee_id) $qq->where('manager_id', $user->employee_id);
                    if ($user->department_id) $qq->orWhere('department_id', $user->department_id);
                });
            })
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
        $this->authorize('employees.delete');
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
            '8000', '2000', '30', 'permanent', '', 'Riyadh', 'Al-Malqa', 'King Saud St', 'Ali Mohamed', '0511111111', 'أخ'
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
        $departments = $Department::forCompany($companyId)->get(['code', 'name']);
        $deptCodes = $departments->map(fn($d) => $d->code ? ($d->name . ' (' . $d->code . ')') : $d->name)->filter()->values()->toArray();

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
        $genderList = ['male', 'female', 'ذكر', 'أنثى'];
        $maritalList = ['single', 'married', 'أعزب', 'متزوج'];
        $contractList = ['permanent', 'temporary', 'probation', 'contractor', 'دائم', 'مؤقت', 'تجربة', 'مقاول'];

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
                           ->setErrorTitle('Input error')
                           ->setError('Value is not in list.')
                           ->setPromptTitle('Pick from list')
                           ->setPrompt('Please select a value from the drop-down list.')
                           ->setFormula1('DataStorage_Hidden!$' . $dataColLetter . '$1:$' . $dataColLetter . '$' . $maxDataRow);
            }
        };

        $applyDropdown('G', 'D', $maxGenderRow); // Gender
        $applyDropdown('H', 'E', $maxMaritalRow); // Marital status
        $applyDropdown('M', 'A', $maxDeptRow); // Main Dept
        $applyDropdown('N', 'A', $maxDeptRow); // Sub Dept
        $applyDropdown('O', 'B', $maxJobRow); // Job Title
        $applyDropdown('P', 'C', $maxManagerRow); // Manager
        $applyDropdown('U', 'F', $maxContractRow); // Contract type

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

        // ✅ NEW: Scoping for import
        $forcedDeptId = null;
        $forcedManagerId = null;
        if (!Auth::user()->can('employees.view.all')) {
            $forcedDeptId = Auth::user()->department_id;
            $forcedManagerId = Auth::user()->employee_id;
        }

        $this->isImporting = true;
        $this->importValidationErrors = [];
        $companyId = $this->getCompanyId();

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        try {
            $allowedBranchIds = DB::table('branch_user_access')
                ->where('user_id', Auth::id())
                ->where('saas_company_id', $companyId)
                ->pluck('branch_id')
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            $defaultBranchId = (int) (Auth::user()?->branch_id ?? 0) ?: null;

            if (! empty($allowedBranchIds) && ! in_array((int) $defaultBranchId, $allowedBranchIds, true)) {
                $defaultBranchId = $allowedBranchIds[0] ?? null;
            }

            $defaultAnnualLeaveDays = 21;
            if (class_exists(\Athka\Saas\Models\SaasCompanyOtherinfo::class)) {
                $defaultAnnualLeaveDays = (int) (\Athka\Saas\Models\SaasCompanyOtherinfo::where('company_id', $companyId)->value('default_annual_leave_days') ?? 21);
            }

            $path = $this->importFile->getRealPath();
            $extension = strtolower($this->importFile->getClientOriginalExtension());

            $DepartmentModel = $this->departmentModelClass();
            $JobTitleModel = $this->jobTitleModelClass();

            $rowCount = 0;
            $importedCount = 0;

            // ─────────────────────────────────────────
            // Helper functions (shared for both formats)
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
                // Handle Excel serial date numbers
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

            // Function to process a single row array (columns 0-27)
            $processRow = function(array $data) use (
                $clean, $parseDate, $extractCode,
                $companyId, $defaultBranchId, $defaultAnnualLeaveDays,
                $forcedDeptId, $forcedManagerId,
                $DepartmentModel, $JobTitleModel,
                &$rowCount, &$importedCount
            ) {
                $rowCount++;
                if (count($data) < 4 || empty(array_filter($data, fn($v) => !is_null($v) && (string)$v !== ''))) return;

                $row = [
                    'name_ar'                    => $clean($data[0] ?? ''),
                    'name_en'                    => $clean($data[1] ?? ''),
                    'national_id'                => $clean($data[2] ?? ''),
                    'national_id_expiry'         => $parseDate($clean($data[3] ?? '')),
                    'nationality'                => $clean($data[4] ?? ''),
                    'birth_date'                 => $parseDate($clean($data[5] ?? '')),
                    'gender'                     => strtolower((string) $clean($data[6] ?? '')),
                    'marital_status'             => strtolower((string) $clean($data[7] ?? '')),
                    'children_count'             => (int) ($clean($data[8] ?? 0) ?: 0),
                    'mobile'                     => $clean($data[9] ?? ''),
                    'email_work'                 => $clean($data[10] ?? ''),
                    'email_personal'             => $clean($data[11] ?? ''),
                    'dept_code'                  => $extractCode($clean($data[12] ?? '')),
                    'sub_dept_code'              => $extractCode($clean($data[13] ?? '')),
                    'job_code'                   => $extractCode($clean($data[14] ?? '')),
                    'manager_emp_no'             => $extractCode($clean($data[15] ?? '')),
                    'hired_at'                   => $parseDate($clean($data[16] ?? '')),
                    'basic_salary'               => (float) ($clean($data[17] ?? 0) ?: 0),
                    'allowances'                 => (float) ($clean($data[18] ?? 0) ?: 0),
                    'annual_leave_days'          => (int) ($clean($data[19] ?? $defaultAnnualLeaveDays) ?: $defaultAnnualLeaveDays),
                    'contract_type'              => strtolower((string) $clean($data[20] ?? '')),
                    'contract_duration_months'   => (int) ($clean($data[21] ?? 0) ?: 0),
                    'city'                       => $clean($data[22] ?? ''),
                    'district'                   => $clean($data[23] ?? ''),
                    'address'                    => $clean($data[24] ?? ''),
                    'emergency_contact_name'     => $clean($data[25] ?? ''),
                    'emergency_contact_phone'    => $clean($data[26] ?? ''),
                    'emergency_contact_relation' => $clean($data[27] ?? ''),
                ];

                // Basic validation
                if (empty($row['name_ar']) || empty($row['national_id'])) {
                    $this->importValidationErrors[] = $this->trp('Row :row: Name AR and National ID are required.', ['row' => $rowCount]);
                    return;
                }

                // Duplicate check
                $duplicate = Employee::where('saas_company_id', $companyId)
                    ->where(function($q) use ($row) {
                        $q->where('national_id', $row['national_id'])
                          ->when($row['email_work'], fn($qq) => $qq->orWhere('email_work', $row['email_work']))
                          ->when($row['mobile'], fn($qq) => $qq->orWhere('mobile', $row['mobile']));
                    })->first();

                if ($duplicate) {
                    $field = tr('National ID');
                    $value = $row['national_id'];
                    if ($row['email_work'] && $duplicate->email_work === $row['email_work']) {
                        $field = tr('Email'); $value = $row['email_work'];
                    } elseif ($row['mobile'] && $duplicate->mobile === $row['mobile']) {
                        $field = tr('Mobile'); $value = $row['mobile'];
                    }
                    $this->importValidationErrors[] = $this->trp('Row :row: Employee already exists matching :field (:value).', [
                        'row' => $rowCount, 'field' => $field, 'value' => $value,
                    ]);
                    return;
                }

                // Find Department
                $deptId = null;
                if (!empty($row['dept_code'])) {
                    $dept = $DepartmentModel::where('saas_company_id', $companyId)
                        ->where(function($q) use ($row) {
                            $q->where('code', $row['dept_code'])->orWhere('name', $row['dept_code']);
                        })->first();
                    if ($dept) $deptId = $dept->id;
                    else $this->importValidationErrors[] = $this->trp('Row :row: Main Department ":code" not found.', ['row' => $rowCount, 'code' => $row['dept_code']]);
                }

                // Find Sub Department
                $subDeptId = null;
                if (!empty($row['sub_dept_code'])) {
                    $subDept = $DepartmentModel::where('saas_company_id', $companyId)
                        ->where(function($q) use ($row) {
                            $q->where('code', $row['sub_dept_code'])->orWhere('name', $row['sub_dept_code']);
                        })->first();
                    if ($subDept) $subDeptId = $subDept->id;
                    else $this->importValidationErrors[] = $this->trp('Row :row: Sub Department ":code" not found.', ['row' => $rowCount, 'code' => $row['sub_dept_code']]);
                }

                // Find Job Title
                $jobId = null;
                if (!empty($row['job_code'])) {
                    $job = $JobTitleModel::where('saas_company_id', $companyId)
                        ->where(function($q) use ($row) {
                            $q->where('code', $row['job_code'])->orWhere('name', $row['job_code']);
                        })->first();
                    if ($job) $jobId = $job->id;
                    else $this->importValidationErrors[] = $this->trp('Row :row: Job title ":code" not found.', ['row' => $rowCount, 'code' => $row['job_code']]);
                }

                // Find Manager
                $managerId = null;
                if (!empty($row['manager_emp_no'])) {
                    $manager = Employee::where('saas_company_id', $companyId)
                        ->where(function($q) use ($row) {
                            $q->where('employee_no', $row['manager_emp_no'])
                              ->orWhere('name_ar', $row['manager_emp_no'])
                              ->orWhere('name_en', $row['manager_emp_no']);
                        })->first();
                    if ($manager) $managerId = $manager->id;
                    else $this->importValidationErrors[] = $this->trp('Row :row: Manager ":no" not found.', ['row' => $rowCount, 'no' => $row['manager_emp_no']]);
                }

                // Gender mapping
                $genderInput = strtolower((string) $row['gender']);
                $gender = (in_array($genderInput, ['female', 'f', 'أنثى', 'انثى'], true)) ? 'female' : 'male';

                // Marital status mapping
                $mStatusInput = strtolower((string) $row['marital_status']);
                $mStatus = (in_array($mStatusInput, ['married', 'm', 'متزوج', 'متزوجة'], true)) ? 'married' : 'single';

                // Contract type mapping
                $contractInput = strtolower((string) $row['contract_type']);
                $contractType = 'permanent';
                if (in_array($contractInput, ['temporary', 'probation', 'contractor', 'مؤقت', 'تجربة', 'مقاول'], true)) {
                    $map = ['مؤقت' => 'temporary', 'تجربة' => 'probation', 'مقاول' => 'contractor'];
                    $contractType = $map[$contractInput] ?? $contractInput;
                }

                try {
                    $jobTitleName = $jobId ? ($JobTitleModel::find($jobId)?->name) : null;

                    Employee::create([
                        'saas_company_id'            => $companyId,
                        'branch_id'                  => $defaultBranchId,
                        'name_ar'                    => $row['name_ar'],
                        'name_en'                    => $row['name_en'],
                        'national_id'                => $row['national_id'],
                        'national_id_expiry'         => (empty($row['national_id_expiry']) || str_contains((string)$row['national_id_expiry'], '#'))
                            ? now()->addYear()->format('Y-m-d')
                            : $row['national_id_expiry'],
                        'nationality'                => $row['nationality'] ?: tr('Unknown'),
                        'birth_date'                 => (empty($row['birth_date']) || str_contains((string)$row['birth_date'], '#'))
                            ? '1990-01-01'
                            : $row['birth_date'],
                        'birth_place'                => $row['city'] ?: tr('Unknown'),
                        'gender'                     => $gender,
                        'marital_status'             => $mStatus,
                        'children_count'             => $row['children_count'] ?: 0,
                        'mobile'                     => $row['mobile'],
                        'email_work'                 => $row['email_work'] ?: null,
                        'email_personal'             => $row['email_personal'] ?: null,
                        'department_id'              => $forcedDeptId ?: $deptId,
                        'sub_department_id'          => $subDeptId,
                        'job_title_id'               => $jobId,
                        'manager_id'                 => $forcedManagerId ?: $managerId,
                        'sector'                     => 'Staff',
                        'grade'                      => 1,
                        'job_function'               => $jobTitleName ?: 'Staff',
                        'hired_at'                   => (empty($row['hired_at']) || str_contains((string)$row['hired_at'], '#'))
                            ? now()->format('Y-m-d')
                            : $row['hired_at'],
                        'basic_salary'               => $row['basic_salary'] ?: 0,
                        'allowances'                 => $row['allowances'] ?: 0,
                        'annual_leave_days'          => $row['annual_leave_days'] ?: $defaultAnnualLeaveDays,
                        'contract_type'              => $contractType,
                        'contract_duration_months'   => $contractType === 'permanent' ? 0 : ($row['contract_duration_months'] ?: 0),
                        'city'                       => $row['city'] ?: tr('Unknown'),
                        'district'                   => $row['district'] ?: tr('Unknown'),
                        'address'                    => $row['address'] ?: tr('Unknown'),
                        'emergency_contact_name'     => $row['emergency_contact_name'] ?: tr('Unknown'),
                        'emergency_contact_phone'    => $row['emergency_contact_phone'] ?: '0000000000',
                        'emergency_contact_relation' => $row['emergency_contact_relation'] ?: 'أخرى',
                        'status'                     => 'ACTIVE',
                    ]);
                    $importedCount++;
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                    if (str_contains($error, 'Data too long')) $error = tr('Some data fields are too long for the database.');
                    elseif (str_contains($error, 'Incorrect date value')) $error = tr('Invalid date format in one of the columns (must be YYYY-MM-DD).');
                    elseif (str_contains($error, "doesn't have a default value")) {
                        preg_match("/Field '(.+)' doesn't have a default value/", $error, $matches);
                        $field = $matches[1] ?? 'unknown';
                        $error = tr('The following required field is missing: ') . $field;
                    } elseif (str_contains($error, 'integrity constraint violation')) {
                        $error = tr('A database constraint was violated. Check if all codes (Department/Job) are correct.');
                    } else {
                        $error = tr('Technical Error: ') . substr($error, 0, 100);
                    }
                    $this->importValidationErrors[] = $this->trp('Row :row: Failed to save: :error', ['row' => $rowCount, 'error' => $error], 'ui');
                }
            };

            // ─────────────────────────────────────────
            // XLSX / XLS: Use PhpSpreadsheet
            // ─────────────────────────────────────────
            if (in_array($extension, ['xlsx', 'xls'])) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray(null, true, true, false);

                // Skip header row (index 0)
                $isFirst = true;
                foreach ($rows as $rowData) {
                    if ($isFirst) { $isFirst = false; continue; }
                    // Skip completely empty rows
                    if (empty(array_filter($rowData, fn($v) => !is_null($v) && (string)$v !== ''))) continue;
                    $processRow($rowData);
                }

            // ─────────────────────────────────────────
            // CSV: Use fgetcsv (original logic)
            // ─────────────────────────────────────────
            } else {
                $content = file_get_contents($path);
                if (!mb_check_encoding($content, 'UTF-8')) {
                    $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16, ISO-8859-1, Windows-1252');
                }
                $tempFile = fopen('php://temp', 'r+');
                fwrite($tempFile, $content);
                rewind($tempFile);

                // Skip BOM
                $bom = fread($tempFile, 3);
                if ($bom !== "\xEF\xBB\xBF") {
                    rewind($tempFile);
                }

                // Detect delimiter
                $firstLine = fgets($tempFile);
                $delimiter = (str_contains($firstLine, ';') && !str_contains($firstLine, ',')) ? ';' : ',';
                rewind($tempFile);
                if ($bom === "\xEF\xBB\xBF") fread($tempFile, 3);

                // Skip header row
                fgets($tempFile);

                while (($data = fgetcsv($tempFile, 0, $delimiter)) !== FALSE) {
                    $processRow($data);
                }

                fclose($tempFile);
            }

        } catch (\Throwable $th) {
            $this->importValidationErrors[] = tr('Critical error during import: ') . $th->getMessage();
        } finally {
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
