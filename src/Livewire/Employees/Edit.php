<?php

namespace Athka\Employees\Livewire\Employees;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

use Athka\Employees\Models\Employee;

class Edit extends Component
{
    use WithFileUploads;

    public Employee $employee;
    public ?int $companyId = null;
    public int $tab = 1;

    /* TAB 1: Basic */
    public string $name_ar = '';
    public ?string $name_en = null;
    public string $national_id = '';
    public string $national_id_expiry = '';
    public string $nationality = '';
    public string $gender = '';
    public string $social_status = '';
    public string $birth_place = '';
    public string $birth_date = '';
    public ?int $children_count = null;

    /* TAB 2: Job */
    public $sector = '';
    public $department_id = null;
    public ?int $sub_department_id = null;
    public $job_title_id = null;
    public $grade = null;
    public $manager_id = null;
    public $manager_name = '';
    public string $hired_at = '';
    public ?string $procedures_start_at = null;

    /* TAB 3: Financial */
    public string $contract_type = '';
    public $basic_salary = null;
    public ?int $contract_duration_months = null;
    public $allowance = null;
    public ?int $annual_leave_days = null;

    // الأجور المشتقة (محسوبة - غير قابلة للتعديل)
    public $daily_wage = null;
    public $hourly_wage = null;
    public $minute_wage = null;

    // الإجازات السنوية
    public bool $is_transferred_employee = false;
    public $opening_leave_balance = null;
    public int $leave_balance_adjustments = 0;
    public $calculated_leave_balance = 0;

    /* TAB 4: Personal */
    public string $mobile = '';
    public ?string $mobile_alt = null;
    public string $email_work = '';
    public ?string $email_personal = null;
    public string $city = '';
    public string $district = '';
    public string $address = '';
    public string $emergency_contact_phone = '';
    public string $emergency_contact_name = '';
    public string $emergency_contact_relation = '';

    /* TAB 5: Documents */
    public $photo = null;
    public $national_id_photo = null;
    public bool $document_verified = false;
    public $qualification = null;
    public $certificates = [];
    public $family_documents = [];
    public $other_documents = [];

    // لعرض الملفات الموجودة
    public $existing_documents = [];
    public $existing_photo = null;
    public $existing_national_id_photo = null;
    public $existing_qualification = null;

    // Certificates / Family / Other are handled by multiple-files component usually via existingFiles array
    public $existing_certificates = [];
    public $existing_family_documents = [];
    public $existing_other_documents = [];
    public $sub_departments = [];

    public function getCompanyId(): int
    {
        return (int) (app()->bound('currentCompany')
            ? app('currentCompany')->id
            : (Auth::user()?->saas_company_id ?? 0));
    }

    public function departmentModelClass(): string
    {
        return class_exists(\Athka\SystemSettings\Models\Department::class)
            ? \Athka\SystemSettings\Models\Department::class
            : \Athka\SystemSettings\Models\Department::class;
    }

    public function jobTitleModelClass(): string
    {
        return class_exists(\Athka\SystemSettings\Models\JobTitle::class)
            ? \Athka\SystemSettings\Models\JobTitle::class
            : \Athka\SystemSettings\Models\JobTitle::class;
    }

    public function mount(int $employeeId): void
    {
        $this->employee = Employee::query()->with('documents')->findOrFail($employeeId);
        $this->companyId = $this->getCompanyId();

        abort_unless($this->employee->saas_company_id === $this->companyId, 404);

        // Tab 1: Basic
        $this->name_ar = $this->employee->name_ar ?? '';
        $this->name_en = $this->employee->name_en;
        $this->national_id = $this->employee->national_id ?? '';
        $this->national_id_expiry = $this->employee->national_id_expiry ? $this->employee->national_id_expiry->format('Y-m-d') : '';
        $this->nationality = $this->employee->nationality ?? '';
        $this->gender = $this->employee->gender ?? '';
        $this->social_status = $this->employee->marital_status ?? '';
        $this->birth_place = $this->employee->birth_place ?? '';
        $this->birth_date = $this->employee->birth_date ? $this->employee->birth_date->format('Y-m-d') : '';
        $this->children_count = $this->employee->children_count;

        // Tab 2: Job
        $this->sector = $this->employee->sector ?? '';
        $this->department_id = $this->employee->department_id;
        $this->sub_department_id = $this->employee->sub_department_id;
        $this->job_title_id = $this->employee->job_title_id;
        $this->grade = $this->employee->grade;
        $this->manager_id = $this->employee->manager_id;
        // Load manager name
        if ($this->manager_id) {
            $manager = Employee::find($this->manager_id);
            if ($manager) {
                $this->manager_name = $manager->name_ar ?? $manager->name_en ?? '';
            }
        }
        $this->hired_at = $this->employee->hired_at ? $this->employee->hired_at->format('Y-m-d') : '';
        $this->procedures_start_at = $this->employee->procedures_start_at ? $this->employee->procedures_start_at->format('Y-m-d') : null;

        // Tab 3: Financial
        $this->contract_type = $this->employee->contract_type ?? '';
        $this->basic_salary = $this->employee->basic_salary;
        $this->contract_duration_months = $this->employee->contract_duration_months;
        $this->allowance = $this->employee->allowances;
        $this->annual_leave_days = $this->employee->annual_leave_days ?? $this->getDefaultAnnualLeaveDays();

        // حساب الأجور المشتقة
        $this->calculateAndUpdateWages();

        // حقول الإجازات
        $this->is_transferred_employee = (bool) $this->employee->is_transferred_employee;
        $this->opening_leave_balance = $this->employee->opening_leave_balance;
        $this->leave_balance_adjustments = $this->employee->leave_balance_adjustments ?? 0;
        $this->calculated_leave_balance = $this->employee->calculateLeaveBalance();

        // Tab 4: Personal
        $this->mobile = $this->employee->mobile ?? '';
        $this->mobile_alt = $this->employee->mobile_alt;
        $this->email_work = $this->employee->email_work ?? '';
        $this->email_personal = $this->employee->email_personal;
        $this->city = $this->employee->city ?? '';
        $this->district = $this->employee->district ?? '';
        $this->address = $this->employee->address ?? '';
        $this->emergency_contact_phone = $this->employee->emergency_contact_phone ?? '';
        $this->emergency_contact_name = $this->employee->emergency_contact_name ?? '';
        $this->emergency_contact_relation = $this->employee->emergency_contact_relation ?? '';        // Tab 5: Documents (Load existing documents)
        $this->document_verified = (bool) $this->employee->documents_verified;

        // Map existing documents
        $this->existing_photo = $this->employee->documents->where('type', 'personal_photo')->first();
        $this->existing_national_id_photo = $this->employee->documents->where('type', 'national_id_photo')->first();
        $this->existing_qualification = $this->employee->documents->where('type', 'qualification')->first();

        // For multiple files, we prepare arrays of [name, url, etc] if supported by component
        // Assuming x-ui.multiple-files supports an array of existing files
        // Let's format them
        $this->existing_certificates = $this->employee->documents->where('type', 'certificates')
            ->map(fn($d) => [
                'id' => $d->id,
                'original_name' => $d->title ?? basename($d->file_path),
                'url' => asset('storage/'.$d->file_path),
                'size' => 0 
            ])->values()->toArray();

        $this->existing_family_documents = $this->employee->documents->where('type', 'family_documents')
            ->map(fn($d) => [
                'id' => $d->id,
                'original_name' => $d->title ?? basename($d->file_path),
                'url' => asset('storage/'.$d->file_path),
                'size' => 0
            ])->values()->toArray();

        $this->existing_other_documents = $this->employee->documents->where('type', 'other_documents')
            ->map(fn($d) => [
                'id' => $d->id,
                'original_name' => $d->title ?? basename($d->file_path),
                'url' => asset('storage/'.$d->file_path),
                'size' => 0
            ])->values()->toArray();

        // Load Sub Departments
        if ($this->department_id) {
            $this->loadSubDepartments($this->department_id);
        }
    }

    public function getDepartmentsProperty()
    {
        $model = $this->departmentModelClass();
        return $model::query()
            ->where('saas_company_id', $this->companyId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($d) => ['value' => $d->id, 'label' => $d->name])
            ->toArray();
    }

    public function getJobTitlesProperty()
    {
        $model = $this->jobTitleModelClass();
        return $model::query()
            ->where('saas_company_id', $this->companyId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($j) => ['value' => $j->id, 'label' => $j->name])
            ->toArray();
    }

    public function getManagersProperty()
    {
        if (!$this->companyId) {
            return [];
        }

        return Employee::where('saas_company_id', $this->companyId)
            ->where('id', '!=', $this->employee->id ?? 0)
            ->get()
            ->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $this->txt($employee->name_ar ?? '', $employee->name_en ?? '') ?: $employee->employee_no,
                ];
            })->toArray();
    }

    public function updatedDepartmentId($value)
    {
        $this->sub_department_id = null;
        $this->manager_id = null;
        $this->sub_departments = [];

        if (!$value) {
            return;
        }

        $model = $this->departmentModelClass();
        $department = $model::find($value);

        if ($department && $department->manager_id) {
            $this->manager_id = $department->manager_id;
        }

        $this->loadSubDepartments($value);
    }

    protected function loadSubDepartments($departmentId)
    {
        $model = $this->departmentModelClass();
        $this->sub_departments = $model::query()
            ->where('saas_company_id', $this->companyId)
            ->where('parent_id', $departmentId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($d) => ['value' => $d->id, 'label' => $d->name])
            ->toArray();
    }

    // Listeners للأجور
    public function updatedBasicSalary($value)
    {
        $this->calculateAndUpdateWages();
    }

    // Listeners للإجازات
    public function updatedIsTransferredEmployee($value)
    {
        if (!$value) {
            $this->opening_leave_balance = null;
            $this->leave_balance_adjustments = 0;
        }
        $this->recalculateLeaveBalance();
    }

    public function addLeaveDay()
    {
        $this->leave_balance_adjustments++;
        $this->recalculateLeaveBalance();
    }

    public function subtractLeaveDay()
    {
        $this->leave_balance_adjustments--;
        $this->recalculateLeaveBalance();
    }

    public function updatedHiredAt($value)
    {
        $this->recalculateLeaveBalance();
    }

    public function updatedOpeningLeaveBalance($value)
    {
        $this->recalculateLeaveBalance();
    }

    private function calculateAndUpdateWages()
    {
        // تحديث الراتب في الموديل للعملية الحسابية الجارية بأمان
        $this->employee->basic_salary = is_numeric($this->basic_salary) ? $this->basic_salary : null;
        
        $wages = $this->employee->calculateWages();
        if ($wages) {
            $this->daily_wage = $wages['daily_wage'];
            $this->hourly_wage = $wages['hourly_wage'];
            $this->minute_wage = $wages['minute_wage'];
        } else {
            $this->daily_wage = null;
            $this->hourly_wage = null;
            $this->minute_wage = null;
        }
    }

    private function recalculateLeaveBalance()
    {
        // تمرير البيانات للموديل لإجراء الحساب بأمان
        $this->employee->is_transferred_employee = $this->is_transferred_employee;
        $this->employee->opening_leave_balance = is_numeric($this->opening_leave_balance) ? $this->opening_leave_balance : 0;
        $this->employee->leave_balance_adjustments = is_numeric($this->leave_balance_adjustments) ? (int)$this->leave_balance_adjustments : 0;
        $this->employee->hired_at = $this->hired_at;

        $this->calculated_leave_balance = $this->employee->calculateLeaveBalance();
    }

    private function isAr(): bool
    {
        return substr((string) app()->getLocale(), 0, 2) === 'ar';
    }

    private function txt(string $ar, string $en): string
    {
        return $this->isAr() ? $ar : $en;
    }

    public function messages(): array
    {
        return [
            'required' => $this->txt('حقل :attribute مطلوب.', 'The :attribute field is required.'),
            'email' => $this->txt('يرجى إدخال بريد إلكتروني صحيح.', 'Please enter a valid email address.'),
            'numeric' => $this->txt('يجب أن يكون الحقل :attribute رقماً.', 'The :attribute must be a number.'),
            'integer' => $this->txt('يجب أن يكون الحقل :attribute عدداً صحيحاً.', 'The :attribute must be an integer.'),
            'date' => $this->txt('يرجى إدخال تاريخ صحيح في :attribute.', 'The :attribute is not a valid date.'),
            'before' => $this->txt('يجب أن يكون التاريخ في :attribute قبل اليوم.', 'The :attribute must be a date before today.'),
            'min' => [
                'numeric' => $this->txt('يجب أن يكون :attribute على الأقل :min.', 'The :attribute must be at least :min.'),
                'file' => $this->txt('يجب أن يكون حجم الملف :attribute على الأقل :min كيلوبايت.', 'The :attribute must be at least :min kilobytes.'),
                'string' => $this->txt('يجب أن يكون طول النص :attribute على الأقل :min حروف.', 'The :attribute must be at least :min characters.'),
            ],
            'max' => [
                'numeric' => $this->txt('يجب أن لا يزيد :attribute عن :max.', 'The :attribute may not be greater than :max.'),
                'file' => $this->txt('يجب أن لا يزيد حجم الملف :attribute عن :max كيلوبايت.', 'The :attribute may not be greater than :max kilobytes.'),
                'string' => $this->txt('يجب أن لا يزيد طول النص :attribute عن :max حروف.', 'The :attribute may not be greater than :max characters.'),
            ],
            'image' => $this->txt('يجب أن يكون الملف :attribute صورة.', 'The :attribute must be an image.'),
            'mimes' => $this->txt('يجب أن يكون ملف :attribute من نوع: :values.', 'The :attribute must be a file of type: :values.'),
        ];
    }

    // Validation rules for each tab
    protected function rulesTab1(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'national_id' => ['required', 'string', 'max:50'],
            'national_id_expiry' => ['required', 'date'],
            'nationality' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'social_status' => ['required', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'birth_place' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
            'children_count' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function rulesTab2(): array
    {
        return [
            'sector' => ['nullable', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'job_title_id' => ['required', 'exists:job_titles,id'],
            'grade' => ['required', 'integer', 'min:1', 'max:10'],
            'hired_at' => ['required', 'date'],
            'procedures_start_at' => ['nullable', 'date'],
        ];
    }

    protected function rulesTab3(): array
    {
        return [
            'contract_type' => ['required', Rule::in(['permanent', 'temporary', 'probation', 'contractor'])],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'contract_duration_months' => ['nullable', 'integer', 'min:1'],
            'allowance' => ['nullable', 'numeric', 'min:0'],
            'annual_leave_days' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function rulesTab4(): array
    {
        return [
            'mobile' => [
                'required',
                'string',
                'max:30',
                Rule::unique('employees', 'mobile')
                    ->where('saas_company_id', $this->companyId)
                    ->where('status', 'ACTIVE')
                    ->whereNull('deleted_at')
                    ->ignore($this->employee->id)
            ],
            'mobile_alt' => ['nullable', 'string', 'max:30'],
            'email_work' => [
                'required',
                'email',
                'max:255',
                Rule::unique('employees', 'email_work')
                    ->where('saas_company_id', $this->companyId)
                    ->where('status', 'ACTIVE')
                    ->whereNull('deleted_at')
                    ->ignore($this->employee->id)
            ],
            'email_personal' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('employees', 'email_personal')
                    ->where('saas_company_id', $this->companyId)
                    ->where('status', 'ACTIVE')
                    ->whereNull('deleted_at')
                    ->ignore($this->employee->id)
            ],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'emergency_contact_phone' => ['required', 'string', 'max:30'],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_relation' => ['required', 'string', 'max:100'],
        ];
    }

    protected function rulesTab5(): array
    {
        return [
             'photo' => ['nullable', 'image', 'max:10240'], // 10MB
             'national_id_photo' => ['nullable', 'image', 'max:10240'],
             'qualification' => ['nullable', 'file', 'max:10240'],
             'certificates.*' => ['nullable', 'file', 'max:10240'],
             'family_documents.*' => ['nullable', 'file', 'max:10240'],
             'other_documents.*' => ['nullable', 'file', 'max:10240'],
        ];
    }

    public function nextTab(): void
    {
        $rules = match ($this->tab) {
            1 => $this->rulesTab1(),
            2 => $this->rulesTab2(),
            3 => $this->rulesTab3(),
            4 => $this->rulesTab4(),
            5 => $this->rulesTab5(),
            default => [],
        };

        if (!empty($rules)) {
            $this->validate($rules);
        }

        if ($this->tab < 5) {
            $this->tab++;
        }
    }

    public function previousTab(): void
    {
        if ($this->tab > 1) {
            $this->tab--;
        }
    }

    public function save(): void
    {
        // Validate all tabs including tab 5
        $this->validate(array_merge(
            $this->rulesTab1(),
            $this->rulesTab2(),
            $this->rulesTab3(),
            $this->rulesTab4(),
            $this->rulesTab5()
        ));

        $this->employee->update([
            // Tab 1
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'national_id' => $this->national_id,
            'national_id_expiry' => $this->national_id_expiry,
            'nationality' => $this->nationality,
            'gender' => $this->gender,
            'marital_status' => $this->social_status,
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date,
            'children_count' => $this->children_count,

            // Tab 2
            'sector' => $this->sector,
            'department_id' => $this->department_id,
            'sub_department_id' => $this->sub_department_id,
            'job_title_id' => $this->job_title_id,
            'grade' => $this->grade,
            'manager_id' => $this->manager_id,
            'hired_at' => $this->hired_at,
            'procedures_start_at' => $this->procedures_start_at,

            // Tab 3
            'contract_type' => $this->contract_type,
            'basic_salary' => $this->basic_salary,
            'daily_wage' => $this->daily_wage,
            'hourly_wage' => $this->hourly_wage,
            'minute_wage' => $this->minute_wage,
            'contract_duration_months' => $this->contract_duration_months,
            'allowances' => $this->allowance,
            'annual_leave_days' => $this->annual_leave_days,
            'is_transferred_employee' => $this->is_transferred_employee,
            'opening_leave_balance' => $this->opening_leave_balance,
            'leave_balance_adjustments' => $this->leave_balance_adjustments,

            // Tab 4
            'mobile' => $this->mobile,
            'mobile_alt' => $this->mobile_alt,
            'email_work' => $this->email_work,
            'email_personal' => $this->email_personal,
            'city' => $this->city,
            'district' => $this->district,
            'address' => $this->address,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_relation' => $this->emergency_contact_relation,

            // Tab 5 Verified Status (Optional update if needed)
            'documents_verified' => $this->document_verified,
        ]);

        // Save Documents (Tab 5)
        $this->saveFile($this->employee, 'photo', 'personal_photo');
        $this->saveFile($this->employee, 'national_id_photo', 'national_id_photo');
        $this->saveFile($this->employee, 'qualification', 'qualification');
        
        $this->saveMultipleFiles($this->employee, 'certificates', 'certificates');
        $this->saveMultipleFiles($this->employee, 'family_documents', 'family_documents');
        $this->saveMultipleFiles($this->employee, 'other_documents', 'other_documents');

        $this->dispatch('toast', type: 'success', title: tr('Saved'), message: tr('Employee updated successfully'));
        $this->dispatch('employee-updated');
        // REMOVED logic that reopens the modal automatically
        // Instead, the view-employee-modal will handle closing on employee-updated via Alpine
    }

    private function saveFile($employee, $property, $type): void
    {
        if (!$this->$property) {
            return;
        }

        // فقط إذا كان ملف جديد (TemporaryUploadedFile)
        if (!($this->$property instanceof TemporaryUploadedFile)) {
            return;
        }

        $path = $this->$property->store("employees/{$employee->id}/documents", 'public');

        // يمكن حذف الملف القديم هنا إذا لزم الأمر

        $employee->documents()->create([
            'type' => $type,
            'file_path' => $path,
            'title' => $this->$property->getClientOriginalName(),
        ]);
    }

    private function saveMultipleFiles($employee, $property, $type): void
    {
        if (empty($this->$property)) {
            return;
        }

        foreach ($this->$property as $file) {
            if (!($file instanceof TemporaryUploadedFile)) {
                continue;
            }

            $path = $file->store("employees/{$employee->id}/documents", 'public');

            $employee->documents()->create([
                'type' => $type,
                'file_path' => $path,
                'title' => $file->getClientOriginalName(),
            ]);
        }
    }

    /**
     * جلب عدد أيام الإجازة السنوية الافتراضية من إعدادات الشركة
     */
    private function getDefaultAnnualLeaveDays(): int
    {
        $settings = \Athka\Saas\Models\SaasCompanyOtherinfo::where('company_id', $this->companyId)->first();
        return $settings->default_annual_leave_days ?? 0;
    }

    public function render()
    {
        return view('employees::livewire.employees.edit');
    }
}




