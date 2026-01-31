<?php

namespace Athka\Employees\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'employees';

    protected static function booted()
    {
        static::creating(function ($employee) {
            if (!$employee->employee_no) {
                $lastNumber = static::where('saas_company_id', $employee->saas_company_id)
                    ->where('employee_no', 'like', 'EMP-%')
                    ->orderByRaw('CAST(SUBSTR(employee_no, 5) AS UNSIGNED) DESC')
                    ->first();

                $nextId = 1;
                if ($lastNumber) {
                    $numericPart = (int) substr($lastNumber->employee_no, 4);
                    $nextId = $numericPart + 1;
                }

                $employee->employee_no = 'EMP-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    protected $fillable = [
        'saas_company_id',
        'employee_no',

        'name_ar',
        'name_en',
        'national_id',
        'national_id_expiry',
        'nationality',
        'birth_date',
        'gender',
        'marital_status',
        'birth_place',
        'children_count',

        'sector',
        'department_id',
        'sub_department_id',
        'job_title_id',
        'grade',
        'job_function',
        'manager_id',
        'hired_at',
        'procedures_start_at',

        'status',
        'ended_at',

        'contract_type',
        'basic_salary',
        'allowances',
        'annual_leave_days',
        'contract_duration_months',

        'mobile',
        'mobile_alt',
        'email_work',
        'email_personal',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_phone_alt',
        'emergency_contact_relation',
        'city',
        'district',
        'address',
        'bank_account_owner',

        'personal_photo_path',
        'id_photo_path',
        'documents_verified',
        'documents_verified_at',
        'documents_verified_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'national_id_expiry' => 'date',
        'hired_at' => 'date',
        'procedures_start_at' => 'date',
        'ended_at' => 'date',

        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'documents_verified' => 'boolean',
        'documents_verified_at' => 'datetime',
    ];

    // Scope: forCompany($id)
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('saas_company_id', $companyId);
    }

    // Relations
    public function department()
    {
        $class = class_exists(\Athka\SystemSettings\Models\Department::class)
            ? \Athka\SystemSettings\Models\Department::class
            : \Athka\SystemSettings\Models\Department::class;

        return $this->belongsTo($class, 'department_id');
    }

    public function subDepartment()
    {
        $class = class_exists(\Athka\SystemSettings\Models\Department::class)
            ? \Athka\SystemSettings\Models\Department::class
            : \Athka\SystemSettings\Models\Department::class;

        return $this->belongsTo($class, 'sub_department_id');
    }

    public function jobTitle()
    {
        $class = class_exists(\Athka\SystemSettings\Models\JobTitle::class)
            ? \Athka\SystemSettings\Models\JobTitle::class
            : \Athka\SystemSettings\Models\JobTitle::class;

        return $this->belongsTo($class, 'job_title_id');
    }

    public function manager(): BelongsTo
{
    return $this->belongsTo(self::class, 'manager_id');
}

public function subordinates(): HasMany
{
    return $this->hasMany(self::class, 'manager_id');
}


    public function verifiedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'documents_verified_by');
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class, 'employee_id');
    }
}




