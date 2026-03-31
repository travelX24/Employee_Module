<?php

namespace Athka\Employees\Livewire\Employees;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use Athka\Employees\Models\Employee;
use Athka\SystemSettings\Models\Department;
use Athka\SystemSettings\Models\JobTitle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Athka\Saas\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class Create extends Component
{
    use WithFileUploads;

    public ?int $companyId = null;
    public ?int $branch_id = null;

    public array $branchOptions = [];
    public array $nationalityOptions = [];

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
    public string $national_id_type = 'national_id';

    /* TAB 2: Job */
    public $sector = '';
    public $department_id = null;
    public ?int $sub_department_id = null;
    public $job_title_id = null;
    public $grade = null;
    public $manager_id = null;
    public string $hired_at = '';

    /* TAB 3: Financial */
    public string $contract_type = '';
    public $basic_salary = null;
    public ?int $contract_duration_months = null;
    public $allowance = null;
    public ?int $annual_leave_days = null;
    public $daily_wage = null;
    public $hourly_wage = null;
    public $minute_wage = null;

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

    public function mount(): void
    {
        $this->authorize('employees.create');
        $this->companyId = auth()->user()->saas_company_id;

        $this->branch_id = auth()->user()->branch_id ?? null;

        if (is_array($allowed = $this->getAllowedBranchIds())) {
            if ($this->branch_id && ! in_array((int) $this->branch_id, $allowed, true)) {
                $this->branch_id = $allowed[0] ?? null;
            }
        }

        $this->loadBranches();
        $this->loadNationalities();

        if (empty($this->hired_at)) {
            $this->hired_at = now()->format('Y-m-d');
        }

        if ($this->annual_leave_days === null) {
            $this->annual_leave_days = $this->getDefaultAnnualLeaveDays();
        }

        if (! Auth::user()->can('employees.view.all')) {
            if (Auth::user()->department_id) {
                $this->department_id = Auth::user()->department_id;
                $this->loadSubDepartments($this->department_id);
            }
            if (Auth::user()->employee_id) {
                $this->manager_id = Auth::user()->employee_id;
            }
        }

        if ($this->department_id) {
            $this->loadSubDepartments($this->department_id);
            $department = Department::find($this->department_id);
            if ($department && $department->manager_id) {
                //
            }
        }

        $this->calculateAndUpdateWages();
        $this->updateLeaveBalancePreview();
    }

    public function updatedHiredAt()
    {
        $this->updateLeaveBalancePreview();
    }

    public function updatedAnnualLeaveDays()
    {
        $this->updateLeaveBalancePreview();
    }

    public function updatedOpeningLeaveBalance()
    {
        $this->updateLeaveBalancePreview();
    }

    public function updatedIsTransferredEmployee()
    {
        $this->updateLeaveBalancePreview();
    }

    private function loadBranches(): void
    {
        if (! $this->companyId) {
            $this->branchOptions = [];

            return;
        }

        $this->branchOptions = Branch::query()
            ->where('saas_company_id', $this->companyId)
            ->when(is_array($allowed = $this->getAllowedBranchIds()), fn ($q) => $q->whereIn('id', $allowed))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(function (Branch $branch): array {
                $label = $branch->name;

                if ($branch->code) {
                    $label .= ' (' . $branch->code . ')';
                }

                return [
                    'value' => $branch->id,
                    'label' => $label,
                ];
            })
            ->toArray();
    }

    public function addLeaveDay()
    {
        $this->leave_balance_adjustments++;
        $this->updateLeaveBalancePreview();
    }

    public function subtractLeaveDay()
    {
        $this->leave_balance_adjustments--;
        $this->updateLeaveBalancePreview();
    }

    private function updateLeaveBalancePreview()
    {
        $tempEmployee = new Employee([
            'saas_company_id' => $this->companyId,
            'branch_id' => $this->branch_id,
            'hired_at' => $this->hired_at,
            'is_transferred_employee' => (bool) $this->is_transferred_employee,
            'opening_leave_balance' => is_numeric($this->opening_leave_balance) ? $this->opening_leave_balance : 0,
            'leave_balance_adjustments' => is_numeric($this->leave_balance_adjustments) ? (int) $this->leave_balance_adjustments : 0,
        ]);

        $this->calculated_leave_balance = (int) round((float) $tempEmployee->calculateLeaveBalance(), 0, PHP_ROUND_HALF_UP);
    }

    private function calculateAndUpdateWages()
    {
        $tempEmployee = new Employee([
            'basic_salary' => is_numeric($this->basic_salary) ? $this->basic_salary : null,
            'saas_company_id' => $this->companyId,
            'branch_id' => $this->branch_id,
        ]);

        $wages = $tempEmployee->calculateWages();

        if ($wages) {
            $this->daily_wage = $wages['daily_wage'];
            $this->hourly_wage = $wages['hourly_wage'];
            $this->minute_wage = $wages['minute_wage'];
        }
    }

    public function updatedBasicSalary()
    {
        $this->calculateAndUpdateWages();
    }

    private function isAr(): bool
    {
        return substr((string) app()->getLocale(), 0, 2) === 'ar';
    }

    private function txt(string $ar, string $en): string
    {
        return $this->isAr() ? $ar : $en;
    }

    private function hasAtLeastThreeNameParts(?string $value): bool
{
    $value = trim((string) $value);

    if ($value === '') {
        return false;
    }

    $parts = preg_split('/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY);

    return count($parts) >= 3;
}

    private function validationMessages(): array
    {
        return [
            'required' => $this->txt('حقل :attribute مطلوب.', 'The :attribute field is required.'),
            'email' => $this->txt('يرجى إدخال بريد إلكتروني صحيح.', 'Please enter a valid email address.'),
            'unique' => $this->txt('قيمة :attribute مستخدمة مسبقاً.', 'The :attribute has already been taken.'),
            'in' => $this->txt('القيمة المختارة في :attribute غير صحيحة.', 'The selected :attribute is invalid.'),
            'date' => $this->txt('يرجى إدخال تاريخ صحيح في :attribute.', 'The :attribute is not a valid date.'),
            'integer' => $this->txt('يرجى إدخال رقم صحيح في :attribute.', 'The :attribute must be an integer.'),
            'numeric' => $this->txt('يرجى إدخال رقم في :attribute.', 'The :attribute must be a number.'),
            'min' => $this->txt('قيمة :attribute يجب ألا تقل عن :min.', 'The :attribute must be at least :min.'),
            'max' => $this->txt('قيمة :attribute يجب ألا تزيد عن :max.', 'The :attribute may not be greater than :max.'),
            'image' => $this->txt('يرجى رفع صورة صالحة في :attribute.', 'The :attribute must be an image.'),
            'file' => $this->txt('يرجى رفع ملف صالح في :attribute.', 'Please upload a valid file for :attribute.'),
            'national_id_expiry.after' => $this->txt('بطاقة الهوية منتهية الصلاحية.', 'The National ID card is expired.'),

            'uploaded' => $this->txt(
                'تعذر رفع الملف. تأكد من نوع الملف وحجمه ثم أعد المحاولة.',
                'The file could not be uploaded. Please check the file type and size, then try again.'
            ),

            'photo.required' => $this->txt('الصورة الشخصية مطلوبة.', 'Personal photo is required.'),
            'national_id_photo.required' => $this->txt('صورة الهوية الوطنية مطلوبة.', 'National ID photo is required.'),

            'photo.max' => $this->txt('حجم الصورة الشخصية يتجاوز 2 ميجابايت.', 'Personal photo size exceeds 2 MB.'),
            'photo.uploaded' => $this->txt('تعذر رفع الصورة الشخصية. تأكد أن حجمها لا يتجاوز 2 ميجابايت.', 'Personal photo upload failed. Make sure it does not exceed 2 MB.'),

            'national_id_photo.max' => $this->txt('حجم صورة الهوية الوطنية يتجاوز 2 ميجابايت.', 'National ID photo size exceeds 2 MB.'),
            'national_id_photo.uploaded' => $this->txt('تعذر رفع صورة الهوية الوطنية. تأكد أن حجمها لا يتجاوز 2 ميجابايت.', 'National ID photo upload failed. Make sure it does not exceed 2 MB.'),

            'qualification.max' => $this->txt('حجم ملف المؤهل يتجاوز 5 ميجابايت.', 'Qualification file size exceeds 5 MB.'),
            'qualification.uploaded' => $this->txt('تعذر رفع ملف المؤهل. تأكد أن حجمه لا يتجاوز 5 ميجابايت.', 'Qualification file upload failed. Make sure it does not exceed 5 MB.'),

            'certificates.*.max' => $this->txt('يوجد ملف في الشهادات يتجاوز 5 ميجابايت.', 'One of the certificate files exceeds 5 MB.'),
            'certificates.*.uploaded' => $this->txt('يوجد ملف في الشهادات تعذر رفعه أو يتجاوز 5 ميجابايت.', 'One of the certificate files failed to upload or exceeds 5 MB.'),

            'family_documents.*.max' => $this->txt('يوجد ملف في الوثائق العائلية يتجاوز 5 ميجابايت.', 'One of the family document files exceeds 5 MB.'),
            'family_documents.*.uploaded' => $this->txt('يوجد ملف في الوثائق العائلية تعذر رفعه أو يتجاوز 5 ميجابايت.', 'One of the family document files failed to upload or exceeds 5 MB.'),

            'other_documents.*.max' => $this->txt('يوجد ملف في الوثائق الأخرى يتجاوز 5 ميجابايت.', 'One of the other document files exceeds 5 MB.'),
            'other_documents.*.uploaded' => $this->txt('يوجد ملف في الوثائق الأخرى تعذر رفعه أو يتجاوز 5 ميجابايت.', 'One of the other document files failed to upload or exceeds 5 MB.'),

            'photo.image' => $this->txt('الصورة الشخصية يجب أن تكون صورة بصيغة JPG أو PNG.', 'Personal photo must be an image in JPG or PNG format.'),
            'national_id_photo.image' => $this->txt('صورة الهوية الوطنية يجب أن تكون صورة بصيغة JPG أو PNG.', 'National ID photo must be an image in JPG or PNG format.'),

            'qualification.mimes' => $this->txt('ملف المؤهل يجب أن يكون PDF أو JPG أو PNG.', 'Qualification file must be PDF, JPG, or PNG.'),
            'certificates.*.mimes' => $this->txt('ملفات الشهادات يجب أن تكون PDF أو JPG أو PNG.', 'Certificate files must be PDF, JPG, or PNG.'),
            'family_documents.*.mimes' => $this->txt('ملفات الوثائق العائلية يجب أن تكون PDF أو JPG أو PNG.', 'Family document files must be PDF, JPG, or PNG.'),
            'other_documents.*.mimes' => $this->txt('ملفات الوثائق الأخرى يجب أن تكون PDF أو JPG أو PNG.', 'Other document files must be PDF, JPG, or PNG.'),
        ];
    }

    private function clearFieldErrorsByPrefix(string $field): void
    {
        $bag = $this->getErrorBag();

        foreach (array_keys($bag->toArray()) as $key) {
            if ($key === $field || str_starts_with($key, $field . '.')) {
                $bag->forget($key);
            }
        }

        $this->setErrorBag($bag);
    }

    public function clearUploadFieldError(string $field): void
    {
        $this->clearFieldErrorsByPrefix($field);
    }

    public function setUploadFieldError(string $field, string $message): void
    {
        $this->clearFieldErrorsByPrefix($field);

        if (trim($message) !== '') {
            $this->addError($field, $message);
        }
    }

    public function setUploadFieldErrors(string $field, array $messages): void
    {
        $this->clearFieldErrorsByPrefix($field);

        foreach ($messages as $message) {
            if (is_string($message) && trim($message) !== '') {
                $this->addError($field, $message);
            }
        }
    }

    private function validateDocumentField(string $field): void
    {
        $this->clearFieldErrorsByPrefix($field);

        $rules = $this->rulesTab5();

        if (! isset($rules[$field])) {
            return;
        }

        $this->validateOnly(
            $field,
            [$field => $rules[$field]],
            $this->validationMessages(),
            $this->validationAttributes()
        );
    }

    private function validateDocumentArrayField(string $field): void
    {
        $this->clearFieldErrorsByPrefix($field);

        $rules = $this->rulesTab5();
        $ruleKey = $field . '.*';

        if (! isset($rules[$ruleKey])) {
            return;
        }

        $validator = Validator::make(
            [$field => $this->{$field}],
            [$ruleKey => $rules[$ruleKey]],
            $this->validationMessages(),
            $this->validationAttributes()
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->addError($key, $message);
                }
            }
        }
    }

    private function getBlockingDocumentErrors(): array
    {
        $documentFields = [
            'photo',
            'national_id_photo',
            'qualification',
            'certificates',
            'family_documents',
            'other_documents',
        ];

        $messages = [];

        foreach ($this->getErrorBag()->toArray() as $key => $fieldMessages) {
            foreach ($documentFields as $field) {
                if ($key === $field || str_starts_with($key, $field . '.')) {
                    $messages[$key] = $fieldMessages;
                    break;
                }
            }
        }

        return $messages;
    }

    private function hasBlockingDocumentErrors(): bool
    {
        return ! empty($this->getBlockingDocumentErrors());
    }

    public function updatedPhoto(): void
    {
        $this->validateDocumentField('photo');
    }

    public function updatedNationalIdPhoto(): void
    {
        $this->validateDocumentField('national_id_photo');
    }

    public function updatedQualification(): void
    {
        $this->validateDocumentField('qualification');
    }

    public function updatedCertificates(): void
    {
        $this->clearFieldErrorsByPrefix('certificates');
    }

    public function updatedFamilyDocuments(): void
    {
        $this->clearFieldErrorsByPrefix('family_documents');
    }

    public function updatedOtherDocuments(): void
    {
        $this->clearFieldErrorsByPrefix('other_documents');
    }

    private function validationAttributes(): array
    {
        return [
            'name_ar' => tr('Arabic Name'),
            'name_en' => tr('English Name'),
            'national_id_type' => tr('ID Type'),
            'national_id' => tr('National ID'),
            'national_id_expiry' => tr('National ID Expiry'),
            'nationality' => tr('Nationality'),
            'gender' => tr('Gender'),
            'social_status' => tr('Social Status'),
            'birth_place' => tr('Birth Place'),
            'birth_date' => tr('Birth Date'),
            'children_count' => tr('Children Count'),

            'sector' => tr('Sector'),
            'department_id' => tr('Main Department'),
            'sub_department_id' => tr('Sub Department'),
            'job_title_id' => tr('Job Title'),
            'grade' => tr('Grade'),
            'manager_id' => tr('Manager'),
            'hired_at' => tr('Hire Date'),

            'contract_type' => tr('Contract Type'),
            'basic_salary' => tr('Basic Salary'),
            'contract_duration_months' => tr('Contract Duration'),
            'allowance' => tr('Allowance'),
            'annual_leave_days' => tr('Annual Leave Days'),

            'mobile' => tr('Mobile'),
            'mobile_alt' => tr('Alternative Mobile'),
            'email_work' => tr('Work Email'),
            'email_personal' => tr('Personal Email'),
            'city' => tr('City'),
            'district' => tr('District'),
            'address' => tr('Address'),
            'emergency_contact_phone' => tr('Emergency Phone'),
            'emergency_contact_name' => tr('Emergency Name'),
            'emergency_contact_relation' => tr('Relation'),

            'photo' => tr('Personal Photo'),
            'national_id_photo' => tr('National ID Photo'),
            'qualification' => tr('Qualification'),
            'certificates' => tr('Certificates'),
            'family_documents' => tr('Family Documents'),
            'other_documents' => tr('Other Documents'),
        ];
    }

   private function rulesTab1(): array
{
    return [
        'name_ar' => [
            'required',
            'string',
            'max:255',
            function ($attribute, $value, $fail) {
                if (! $this->hasAtLeastThreeNameParts($value)) {
                    $fail($this->txt(
                        'يجب إدخال الاسم الثلاثي على الأقل.',
                        'Please enter at least three names.'
                    ));
                }
            },
        ],
        'national_id_type' => ['required', Rule::in(['national_id', 'iqama', 'passport', 'other'])],
        'national_id' => [
            'required',
            'string',
            'max:50',
            Rule::unique('employees', 'national_id')
                ->where('saas_company_id', $this->companyId)
                ->whereNull('deleted_at'),
        ],
        
            'national_id_type' => ['required', Rule::in(['national_id', 'iqama', 'passport', 'other'])],
            'national_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employees', 'national_id')
                    ->where('saas_company_id', $this->companyId)
                    ->whereNull('deleted_at'),
            ],
            'national_id_expiry' => ['required', 'date', 'after:today'],
            'nationality' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'social_status' => ['required', Rule::in(['single', 'married'])],
            'birth_place' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'children_count' => ['nullable', 'integer', 'min:0'],
        ];
    }

    private function rulesTab2(): array
    {
        return [
            'sector' => ['nullable', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'job_title_id' => ['required', 'exists:job_titles,id'],
            'grade' => ['required', 'integer', 'min:1', 'max:10'],
            'manager_id' => ['nullable', 'exists:employees,id'],
            'hired_at' => ['required', 'date'],

            'contract_type' => ['required', Rule::in(['permanent', 'temporary', 'probation', 'contractor', 'freelancer'])],
            'contract_duration_months' => Rule::when(
                ($this->contract_type !== '' && $this->contract_type !== 'permanent'),
                ['required', 'integer', 'min:1'],
                ['nullable', 'integer', 'min:1']
            ),

            'sub_department_id' => ['nullable', 'exists:departments,id'],
        ];
    }

    private function rulesTab3(): array
    {
        return [
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'allowance' => ['nullable', 'numeric', 'min:0'],
            'annual_leave_days' => ['nullable', 'integer', 'min:0'],
        ];
    }

    private function rulesTab4(): array
    {
        return [
            'mobile' => [
                'required',
                'string',
                'max:20',
                'regex:/^\d+$/',
                Rule::unique('employees', 'mobile')
                    ->where('saas_company_id', $this->companyId)
                    ->where('status', 'ACTIVE')
                    ->whereNull('deleted_at'),
            ],
            'email_work' => [
                'required',
                'email',
                Rule::unique('employees', 'email_work')
                    ->where('saas_company_id', $this->companyId)
                    ->where('status', 'ACTIVE')
                    ->whereNull('deleted_at'),
            ],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^\d+$/',
            ],
            'emergency_contact_name' => ['required', 'string', 'max:100'],
            'emergency_contact_relation' => ['required', Rule::in(['أب', 'أم', 'أخ', 'أخت', 'زوج', 'زوجة', 'ابن', 'بنت', 'أخرى'])],
            'mobile_alt' => ['nullable', 'string', 'max:20', 'regex:/^\d+$/'],
            'email_personal' => [
                'nullable',
                'email',
                Rule::unique('employees', 'email_personal')
                    ->where('saas_company_id', $this->companyId)
                    ->where('status', 'ACTIVE')
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    private function rulesTab5(): array
    {
        return [
            'photo' => ['required', 'image', 'max:2048'],
            'national_id_photo' => ['required', 'image', 'max:2048'],
            'qualification' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'certificates.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'family_documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'other_documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    private function rulesForTab(int $tab): array
    {
        return match ($tab) {
            1 => $this->rulesTab1(),
            2 => $this->rulesTab2(),
            3 => $this->rulesTab3(),
            4 => $this->rulesTab4(),
            5 => $this->rulesTab5(),
            default => [],
        };
    }

    public function goToTab(int $target): void
    {
        $target = max(1, min(5, $target));

        if ($target < $this->tab) {
            $this->tab = $target;
            return;
        }

        if ($target > $this->tab) {
            for ($t = $this->tab; $t < $target; $t++) {
                $this->validate(
                    $this->rulesForTab($t),
                    $this->validationMessages(),
                    $this->validationAttributes()
                );
            }
        }

        $this->tab = $target;
    }

    public function nextTab(): void
    {
        $this->validate(
            $this->rulesForTab($this->tab),
            $this->validationMessages(),
            $this->validationAttributes()
        );

        $this->tab = min(5, $this->tab + 1);
    }

    public function prevTab(): void
    {
        $this->tab = max(1, $this->tab - 1);
    }

    public function store()
    {
        $this->authorize('employees.create');

        try {
            if (! Auth::user()->can('employees.view.all')) {
                if (Auth::user()->department_id) {
                    $this->department_id = Auth::user()->department_id;
                }
                if (Auth::user()->employee_id) {
                    $this->manager_id = Auth::user()->employee_id;
                }
            }

            if ($this->hasBlockingDocumentErrors()) {
                throw \Illuminate\Validation\ValidationException::withMessages(
                    $this->getBlockingDocumentErrors()
                );
            }

            $this->validate(array_merge(
                $this->rulesTab1(),
                $this->rulesTab2(),
                $this->rulesTab3(),
                $this->rulesTab4(),
                $this->rulesTab5(),
            ), $this->validationMessages(), $this->validationAttributes());

            $jobTitle = JobTitle::find($this->job_title_id);

            $data = [
                'saas_company_id' => $this->companyId,
                'branch_id' => $this->branch_id,
                'name_ar' => $this->name_ar,
                'name_en' => $this->name_en,
                'national_id' => $this->national_id,
                'national_id_expiry' => $this->national_id_expiry,
                'nationality' => $this->nationality,
                'birth_date' => $this->birth_date,
                'gender' => $this->gender,
                'marital_status' => $this->social_status,
                'birth_place' => $this->birth_place,
                'children_count' => $this->children_count,

                'sector' => $this->sector ?: 'Staff',
                'department_id' => $this->department_id,
                'sub_department_id' => $this->sub_department_id,
                'job_title_id' => $this->job_title_id,
                'grade' => $this->grade,
                'job_function' => $jobTitle ? $jobTitle->name : 'Staff',
                'manager_id' => $this->manager_id,
                'hired_at' => $this->hired_at,

                'status' => 'ACTIVE',
                'contract_type' => $this->contract_type,
                'basic_salary' => $this->basic_salary,
                'allowances' => $this->allowance ?: 0,
                'annual_leave_days' => $this->annual_leave_days ?: 0,
                'contract_duration_months' => $this->contract_duration_months ?: 0,

                'mobile' => $this->mobile,
                'mobile_alt' => $this->mobile_alt,
                'email_work' => $this->email_work ?: null,
                'email_personal' => $this->email_personal ?: null,
                'emergency_contact_phone' => $this->emergency_contact_phone,
                'emergency_contact_name' => $this->emergency_contact_name,
                'emergency_contact_relation' => $this->emergency_contact_relation,
                'city' => $this->city,
                'district' => $this->district,
                'address' => $this->address,
            ];

            if (is_array($allowed = $this->getAllowedBranchIds())) {
                abort_unless($this->branch_id && in_array((int) $this->branch_id, $allowed, true), 403);
            }

            $employee = Employee::create($data);

            $this->saveFile($employee, 'photo', 'personal_photo');
            $this->saveFile($employee, 'national_id_photo', 'national_id_photo');
            $this->saveFile($employee, 'qualification', 'qualification');

            $this->saveMultipleFiles($employee, 'certificates', 'certificates');
            $this->saveMultipleFiles($employee, 'family_documents', 'family_documents');
            $this->saveMultipleFiles($employee, 'other_documents', 'other_documents');

            session()->flash('status', tr('Employee created successfully'));
            session()->flash('employee_id', $employee->id);

            return $this->redirectRoute('company-admin.employees.index', navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', tr('Failed to create employee. Please try again. Error: ') . $e->getMessage());
            return $this->redirectRoute('company-admin.employees.create', navigate: true);
        }
    }

    private function saveFile($employee, $property, $type): void
    {
        if (! $this->$property) {
            return;
        }

        $path = $this->$property->store("employees/{$employee->id}/documents", 'public');

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
            $path = $file->store("employees/{$employee->id}/documents", 'public');

            $employee->documents()->create([
                'type' => $type,
                'file_path' => $path,
                'title' => $file->getClientOriginalName(),
            ]);
        }
    }

    public function getDepartmentsProperty()
    {
        if (! $this->companyId) {
            return [];
        }

        return Department::forCompany($this->companyId)
            ->active()
            ->whereNull('parent_id')
            ->when(! Auth::user()->can('employees.view.all'), function ($q) {
                if ($deptId = Auth::user()->department_id) {
                    $q->where('id', $deptId);
                } else {
                    $q->where('id', 0);
                }
            })
            ->get()
            ->map(function ($department) {
                return [
                    'value' => $department->id,
                    'label' => $department->name,
                ];
            })->toArray();
    }

    public $sub_departments = [];

    public function updatedDepartmentId($value)
    {
        $this->sub_department_id = null;
        $this->manager_id = null;
        $this->sub_departments = [];

        if (! $value) {
            return;
        }

        $this->loadSubDepartments($value);

        $department = Department::find($value);

        if ($department && $department->manager_id) {
            $this->manager_id = $department->manager_id;
        }
    }

    protected function loadSubDepartments($departmentId)
    {
        $this->sub_departments = Department::forCompany($this->companyId)
            ->where('parent_id', $departmentId)
            ->active()
            ->get()
            ->map(function ($department) {
                return [
                    'value' => $department->id,
                    'label' => $department->name,
                ];
            })->toArray();
    }

    public function getJobTitlesProperty()
    {
        if (! $this->companyId) {
            return [];
        }

        return JobTitle::where('saas_company_id', $this->companyId)
            ->get()
            ->map(function ($jobTitle) {
                return [
                    'value' => $jobTitle->id,
                    'label' => $jobTitle->name,
                ];
            })->toArray();
    }

    public function getManagersProperty()
    {
        if (! $this->companyId) {
            return [];
        }

        return Employee::where('saas_company_id', $this->companyId)
            ->where('id', '!=', $this->employee_id ?? 0)
            ->when(! Auth::user()->can('employees.view.all'), function ($q) {
                $user = Auth::user();
                $q->where(function ($qq) use ($user) {
                    if ($user->employee_id) {
                        $qq->where('manager_id', $user->employee_id)
                            ->orWhere('id', $user->employee_id);
                    }
                    if ($user->department_id) {
                        $qq->orWhere('department_id', $user->department_id);
                    }
                    if (! $user->employee_id && ! $user->department_id) {
                        $qq->where('id', 0);
                    }
                });
            })
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->name_ar ?: $employee->name_en,
                ];
            })->toArray();
    }

    public function formatFileSize($bytes)
    {
        if ($bytes === 0 || $bytes === null) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return number_format($bytes / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }

    private function getDefaultAnnualLeaveDays(): int
    {
        $settings = \Athka\Saas\Models\SaasCompanyOtherinfo::where('company_id', $this->companyId)->first();
        return $settings->default_annual_leave_days ?? 0;
    }

    public function render()
    {
        return view('employees::livewire.employees.create')
            ->layout('layouts.company-admin');
    }

    public function getBranchesProperty(): array
    {
        return $this->branchOptions;
    }

    private function getAllowedBranchIds(): ?array
    {
        $user = Auth::user();
        if (! $user) return null;

        $companyId = (int) ($user->saas_company_id ?? 0);
        if (! $companyId) return null;

        $ids = DB::table('branch_user_access')
            ->where('user_id', $user->id)
            ->where('saas_company_id', $companyId)
            ->pluck('branch_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        if (! empty($ids)) return $ids;

        return null;
    }

    public function removeUploadItem(string $field, int $index): void
    {
        $allowed = ['certificates', 'family_documents', 'other_documents'];

        if (! in_array($field, $allowed, true)) {
            return;
        }

        $current = $this->{$field};

        if (! is_array($current)) {
            $this->{$field} = [];
            $this->clearFieldErrorsByPrefix($field);
            return;
        }

        if (array_key_exists($index, $current)) {
            unset($current[$index]);
            $this->{$field} = array_values($current);
        }

        $this->clearFieldErrorsByPrefix($field);
    }

    public function updatedContractType($value): void
    {
        if ($value === 'permanent') {
            $this->contract_duration_months = null;
        }
    }

    private function loadNationalities(): void
    {
        $this->nationalityOptions = [];

        $cfg = config('nationalities');
        if (is_array($cfg) && count($cfg) > 0) {
            $this->nationalityOptions = collect($cfg)
                ->filter(fn ($v) => is_string($v) && trim($v) !== '')
                ->values()
                ->map(fn ($v) => ['value' => $v, 'label' => $v])
                ->all();
            return;
        }

        $table = null;
        if (Schema::hasTable('nationalities')) $table = 'nationalities';
        elseif (Schema::hasTable('countries')) $table = 'countries';

        if (! $table) {
            $fallback = ['Yemen', 'Saudi Arabia', 'United Arab Emirates', 'Qatar', 'Kuwait', 'Oman', 'Bahrain', 'Egypt', 'Jordan', 'Iraq', 'Syria', 'Lebanon', 'Sudan', 'Morocco', 'Tunisia', 'Algeria'];
            $this->nationalityOptions = collect($fallback)->map(fn ($v) => ['value' => $v, 'label' => $v])->all();
            return;
        }

        $cols = Schema::getColumnListing($table);
        $labelCol = null;

        if ($this->isAr()) {
            foreach (['nationality_ar', 'name_ar', 'arabic_name', 'name_arabic'] as $c) {
                if (in_array($c, $cols, true)) {
                    $labelCol = $c;
                    break;
                }
            }
        } else {
            foreach (['nationality_en', 'name_en', 'english_name', 'name_english'] as $c) {
                if (in_array($c, $cols, true)) {
                    $labelCol = $c;
                    break;
                }
            }
        }

        if (! $labelCol) {
            foreach (['nationality', 'name', 'title'] as $c) {
                if (in_array($c, $cols, true)) {
                    $labelCol = $c;
                    break;
                }
            }
        }

        if (! $labelCol) {
            $this->nationalityOptions = [];
            return;
        }

        $rows = DB::table($table)
            ->select($labelCol)
            ->whereNotNull($labelCol)
            ->where($labelCol, '!=', '')
            ->distinct()
            ->orderBy($labelCol)
            ->limit(500)
            ->get();

        $this->nationalityOptions = $rows->map(function ($r) use ($labelCol) {
            $v = (string) $r->{$labelCol};
            return ['value' => $v, 'label' => $v];
        })->values()->all();
    }
}