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
        'daily_wage',
        'hourly_wage',
        'minute_wage',
        'allowances',
        'annual_leave_days',
        'is_transferred_employee',
        'opening_leave_balance',
        'leave_balance_adjustments',
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
        'daily_wage' => 'decimal:2',
        'hourly_wage' => 'decimal:2',
        'minute_wage' => 'decimal:4',
        'allowances' => 'decimal:2',
        'is_transferred_employee' => 'boolean',
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

    public function activeWorkSchedule()
    {
        return $this->hasOne(\App\Modules\Attendance\Models\EmployeeWorkSchedule::class, 'employee_id')
            ->where('is_active', true)
            ->with('workSchedule.periods');
    }

    /**
     * حساب الأجور المشتقة من الراتب الأساسي وجدول العمل
     * 
     * سياسة التقريب الموحدة:
     * - الأجر اليومي: تقريب لأقرب ريال صحيح
     * - الأجر بالساعة: تقريب لأقرب نصف ريال (0.50)
     * - الأجر بالدقيقة: تقريب لرقمين عشريين
     */
    public function calculateWages()
    {
        if (!$this->basic_salary || $this->basic_salary <= 0) {
            return null;
        }

        // الأجر اليومي = الراتب الأساسي ÷ 30
        $dailyWage = $this->basic_salary / 30;

        // جلب جدول العمل النشط للموظف
        $activeSchedule = $this->activeWorkSchedule;

        if (!$activeSchedule || !$activeSchedule->workSchedule) {
            // لا يوجد جدول عمل - نستخدم 8 ساعات كقيمة افتراضية
            $totalDailyHours = 8;
        } else {
            // حساب إجمالي ساعات العمل اليومية من periods
            $totalDailyMinutes = 0;
            foreach ($activeSchedule->workSchedule->periods as $period) {
                $totalDailyMinutes += $this->calculateMinutesBetween(
                    $period->start_time,
                    $period->end_time,
                    $period->is_night_shift
                );
            }
            $totalDailyHours = $totalDailyMinutes / 60;
        }

        // الحسابات مع سياسة التقريب الموحدة
        $hourlyWage = $totalDailyHours > 0 ? $dailyWage / $totalDailyHours : 0;
        $minuteWage = $hourlyWage / 60;

        return [
            // تقريب اليومي لأقرب ريال صحيح (267 بدل 266.67)
            'daily_wage' => round($dailyWage, 0),
            
            // تقريب الساعة لأقرب نصف ريال (33.50 بدل 33.33)
            'hourly_wage' => round($hourlyWage * 2) / 2,
            
            // تقريب الدقيقة لرقمين عشريين (0.56 بدل 0.5556)
            'minute_wage' => round($minuteWage, 2),
        ];
    }

    /**
     * حساب الدقائق بين وقتين مع مراعاة الورديات الليلية
     */
    private function calculateMinutesBetween($startTime, $endTime, $isNightShift = false)
    {
        try {
            $start = \Carbon\Carbon::parse($startTime);
            $end = \Carbon\Carbon::parse($endTime);

            if ($isNightShift && $end->lt($start)) {
                $end->addDay();
            }

            return $start->diffInMinutes($end);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * حساب رصيد الإجازة السنوية
     * - موظف منقول: الرصيد الافتتاحي + التعديلات (يمكن أن يكون سالب)
     * - موظف جديد: حساب بناءً على تاريخ التعيين (كل 30 يوم = شهر)
     * - إذا لم تُهيأ الإجازات في إعدادات الشركة: يرجع 0
     */
    /**
     * حساب رصيد الإجازة السنوية
     * - موظف منقول: الرصيد الافتتاحي + التعديلات (يمكن أن يكون سالب)
     * - موظف جديد: حساب نسبي بناءً على تاريخ التوظيف حتى نهاية السنة الحالية
     */
    public function calculateLeaveBalance()
    {
        if ($this->is_transferred_employee) {
            return ($this->opening_leave_balance ?? 0) + ($this->leave_balance_adjustments ?? 0);
        } else {
            if (!$this->hired_at) {
                return 0;
            }

            // جلب الإجازة السنوية الكاملة من إعدادات الشركة
            $companySettings = \Athka\Saas\Models\SaasCompanyOtherinfo::where('company_id', $this->saas_company_id)->first();
            $defaultDays = $companySettings->default_annual_leave_days ?? 0;
            if ($defaultDays == 0) return ($this->leave_balance_adjustments ?? 0);

            $hiredDate = \Carbon\Carbon::parse($this->hired_at);
            $currentYear = now()->year;
            $hiredYear = $hiredDate->year;

            if ($hiredYear < $currentYear) {
                // موظف قديم: يستحق الرصيد كاملاً للسنة الحالية
                $earnedDays = $defaultDays;
            } elseif ($hiredYear == $currentYear) {
                // موظف جديد في السنة الحالية: حساب نسبي حتى نهاية السنة
                $endOfYear = $hiredDate->copy()->endOfYear();
                $daysInYear = $hiredDate->copy()->startOfYear()->diffInDays($endOfYear) + 1;
                $daysRemainingInYear = $hiredDate->diffInDays($endOfYear) + 1;
                $earnedDays = ($daysRemainingInYear / $daysInYear) * $defaultDays;
            } else {
                // موظف سيبدأ في سنة مستقبلية
                $earnedDays = 0;
            }

            return round($earnedDays, 1) + ($this->leave_balance_adjustments ?? 0);
        }
    }
}




