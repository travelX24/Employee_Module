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

class Create extends Component
{
    use WithFileUploads;

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
    public string $hired_at = '';

    /* TAB 3: Financial */
    public string $contract_type = '';
    public $basic_salary = null;
    public ?int $contract_duration_months = null;
    public $allowance = null;
    public ?int $annual_leave_days = null;

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
        $this->companyId = auth()->user()->saas_company_id;

        // تعيين تاريخ اليوم لحقل التوظيف
        if (empty($this->hired_at)) {
            $this->hired_at = now()->format('Y-m-d');
        }
    }

    private function isAr(): bool
    {
        return substr((string) app()->getLocale(), 0, 2) === 'ar';
    }

    private function txt(string $ar, string $en): string
    {
        return $this->isAr() ? $ar : $en;
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

            // Custom Document Messages
            'photo.required' => $this->txt('الصورة الشخصية وصورة الهوية الوطنية مطلوبة.', 'Personal Photo and National ID Photo are required.'),
            'national_id_photo.required' => $this->txt('الصورة الشخصية وصورة الهوية الوطنية مطلوبة.', 'Personal Photo and National ID Photo are required.'),
            
            'photo.max' => $this->txt('الحد الأقصى لحجم الملف: 2 ميجابايت للصور، و 5 ميجابايت للمستندات الأخرى.', 'Maximum file size: 2MB for photos, 5MB for other documents.'),
            'national_id_photo.max' => $this->txt('الحد الأقصى لحجم الملف: 2 ميجابايت للصور، و 5 ميجابايت للمستندات الأخرى.', 'Maximum file size: 2MB for photos, 5MB for other documents.'),
            'qualification.max' => $this->txt('الحد الأقصى لحجم الملف: 2 ميجابايت للصور، و 5 ميجابايت للمستندات الأخرى.', 'Maximum file size: 2MB for photos, 5MB for other documents.'),
            'certificates.*.max' => $this->txt('الحد الأقصى لحجم الملف: 2 ميجابايت للصور، و 5 ميجابايت للمستندات الأخرى.', 'Maximum file size: 2MB for photos, 5MB for other documents.'),
            'family_documents.*.max' => $this->txt('الحد الأقصى لحجم الملف: 2 ميجابايت للصور، و 5 ميجابايت للمستندات الأخرى.', 'Maximum file size: 2MB for photos, 5MB for other documents.'),
            'other_documents.*.max' => $this->txt('الحد الأقصى لحجم الملف: 2 ميجابايت للصور، و 5 ميجابايت للمستندات الأخرى.', 'Maximum file size: 2MB for photos, 5MB for other documents.'),

            'photo.image' => $this->txt('الصيغ المقبولة: JPG, PNG, PDF', 'Accepted formats: JPG, PNG, PDF'),
            'national_id_photo.image' => $this->txt('الصيغ المقبولة: JPG, PNG, PDF', 'Accepted formats: JPG, PNG, PDF'),
            'qualification.mimes' => $this->txt('الصيغ المقبولة: JPG, PNG, PDF', 'Accepted formats: JPG, PNG, PDF'),
            'certificates.*.mimes' => $this->txt('الصيغ المقبولة: JPG, PNG, PDF', 'Accepted formats: JPG, PNG, PDF'),
            'family_documents.*.mimes' => $this->txt('الصيغ المقبولة: JPG, PNG, PDF', 'Accepted formats: JPG, PNG, PDF'),
            'other_documents.*.mimes' => $this->txt('الصيغ المقبولة: JPG, PNG, PDF', 'Accepted formats: JPG, PNG, PDF'),
        ];
    }

    private function validationAttributes(): array
    {
        return [
            'name_ar' => tr('Arabic Name'),
            'name_en' => tr('English Name'),
            'national_id' => tr('National ID'),
            'national_id_expiry' => tr('National ID Expiry'),
            'nationality' => tr('Nationality'),
            'gender' => tr('Gender'),
            'social_status' => tr('Social Status'),
            'birth_place' => tr('Birth Place'),
            'birth_date' => tr('Birth Date'),
            'children_count' => tr('Children Count'),
            
            'sector' => tr('Sector'),
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
            'name_ar' => ['required', 'string', 'max:255'],
            'national_id' => ['required', 'string', 'max:50', 'unique:employees,national_id'],
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
            
            'sub_department_id' => ['nullable', 'exists:departments,id'],
        ];
    }

    private function rulesTab3(): array
    {
        return [
            'contract_type' => ['required', Rule::in(['permanent', 'temporary', 'probation', 'contractor'])],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            
            'contract_duration_months' => ['nullable', 'integer', 'min:1'],
            'allowance' => ['nullable', 'numeric', 'min:0'],
            'annual_leave_days' => ['nullable', 'integer', 'min:0'],
        ];
    }

    private function rulesTab4(): array
    {
        return [
            'mobile' => ['required', 'string', 'max:30'],
            'email_work' => ['required', 'email'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:30'],
            'emergency_contact_name' => ['required', 'string', 'max:100'],
            'emergency_contact_relation' => ['required', 'string', 'max:50'],
            
            'mobile_alt' => ['nullable', 'string', 'max:30'],
            'email_personal' => ['nullable', 'email'],

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
        try {
            $this->validate(array_merge(
                $this->rulesTab1(),
                $this->rulesTab2(),
                $this->rulesTab3(),
                $this->rulesTab4(),
                $this->rulesTab5(),
            ), $this->validationMessages(), $this->validationAttributes());

            // Prepare correct mapping for DB
            $jobTitle = JobTitle::find($this->job_title_id);
 
            $data = [
                'saas_company_id' => $this->companyId,
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

            $employee = Employee::create($data);

            // حفظ الصور والملفات
            $this->saveFile($employee, 'photo', 'personal_photo');
            $this->saveFile($employee, 'national_id_photo', 'national_id_photo');
            $this->saveFile($employee, 'qualification', 'qualification');

            // حفظ الملفات المتعددة
            $this->saveMultipleFiles($employee, 'certificates', 'certificates');
            $this->saveMultipleFiles($employee, 'family_documents', 'family_documents');
            $this->saveMultipleFiles($employee, 'other_documents', 'other_documents');

            return redirect()->route('company-admin.employees.index')
                ->with('status', tr('Employee created successfully'))
                ->with('employee_id', $employee->id);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', tr('Failed to create employee. Please try again. Error: ') . $e->getMessage());
            return redirect()->route('company-admin.employees.create');
        }
    }

    private function saveFile($employee, $property, $type): void
    {
        if (!$this->$property) {
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
        if (!$this->companyId) {
            return [];
        }

        return Department::forCompany($this->companyId)
            ->active()
            ->whereNull('parent_id')
            ->get()
            ->map(function($department) {
                return [
                    'value' => $department->id,
                    'label' => $department->name,
                ];
            })->toArray();
    }

    public function updatedDepartmentId($value)
    {
        $this->sub_department_id = null;
        $this->manager_id = null;
        
        if (!$value) {
            return;
        }

        $department = Department::find($value);

        if ($department && $department->manager_id) {
            $this->manager_id = $department->manager_id;
        }
    }

    public function getJobTitlesProperty()
    {
        if (!$this->companyId) {
            return [];
        }

        return JobTitle::where('saas_company_id', $this->companyId)
            ->get()
            ->map(function($jobTitle) {
            return [
                'value' => $jobTitle->id,
                'label' => $jobTitle->name,
            ];
        })->toArray();
    }

    public function getManagersProperty()
    {
        if (!$this->companyId) {
            return [];
        }

        return Employee::where('saas_company_id', $this->companyId)
            ->where('id', '!=', $this->employee_id ?? 0)
            ->get()
            ->map(function($employee) {
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

    public function render()
    {
        $subDepartments = [];
        
        if ($this->department_id) {
            $subDepartments = Department::forCompany($this->companyId)
                ->where('parent_id', $this->department_id)
                ->active()
                ->get()
                ->map(function($department) {
                    return [
                        'value' => $department->id,
                        'label' => $department->name,
                    ];
                })->toArray();
        }

        return view('employees::livewire.employees.create', [
                'subDepartments' => $subDepartments
            ])
            ->layout('layouts.company-admin');
    }

}



