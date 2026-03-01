<?php

namespace Athka\Employees\Http\Controllers\Api;

use Athka\AuthKit\Support\UiMsg;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;      // ✅ NEW
use Illuminate\Support\Facades\Schema;  // ✅ NEW

class EmployeeController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok'      => false,
                'error'   => 'unauthenticated',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // ✅ نفس شرط التطبيق: لازم يكون مرتبط بموظف
        if ((bool) config('authkit.api.employees_only', true)) {
            $hasEmployeeId = !empty($user->employee_id);

            $employeeExists = $hasEmployeeId;
            if ($hasEmployeeId && method_exists($user, 'employee')) {
                $employeeExists = $user->employee()->exists();
            }

            if (! $employeeExists) {
                $msg = function_exists('tr')
                    ? tr('This account is not allowed to use the mobile app.')
                    : 'This account is not allowed to use the mobile app.';

                return response()->json([
                    'ok'      => false,
                    'error'   => 'not_mobile_user',
                    'message' => $msg,
                ], 403);
            }
        }

        // ✅ Load employee
        if (method_exists($user, 'loadMissing') && method_exists($user, 'employee')) {
            $user->loadMissing(['employee']);
        }

        $employee = $user->employee ?? null;

        // ✅ Load employee relations safely
        if ($employee && method_exists($employee, 'loadMissing')) {
            $rels = [];
            if (method_exists($employee, 'department')) $rels[] = 'department';
            if (method_exists($employee, 'jobTitle'))   $rels[] = 'jobTitle';
            if (method_exists($employee, 'job_title'))  $rels[] = 'job_title';
            if (method_exists($employee, 'documents'))  $rels[] = 'documents';

            if (!empty($rels)) {
                $employee->loadMissing($rels);
            }
        }

        // ✅ Company
        $company = null;
        $companyInfo = null;

        if (!empty($user->saas_company_id)) {
            $saasCompanyClass = class_exists(\Athka\Saas\Models\SaasCompany::class)
                ? \Athka\Saas\Models\SaasCompany::class
                : (class_exists(\App\Modules\Saas\Models\SaasCompany::class)
                    ? \App\Modules\Saas\Models\SaasCompany::class
                    : null);

            $saasOtherInfoClass = class_exists(\Athka\Saas\Models\SaasCompanyOtherinfo::class)
                ? \Athka\Saas\Models\SaasCompanyOtherinfo::class
                : (class_exists(\App\Modules\Saas\Models\SaasCompanyOtherinfo::class)
                    ? \App\Modules\Saas\Models\SaasCompanyOtherinfo::class
                    : null);

            if ($saasCompanyClass) {
                $company = $saasCompanyClass::find($user->saas_company_id);
            }

            if ($saasOtherInfoClass) {
                $companyInfo = $saasOtherInfoClass::where('company_id', $user->saas_company_id)->first();
            }
        }

        $subscriptionEndsAt = null;
        if ($companyInfo && isset($companyInfo->subscription_ends_at)) {
            $endsAt = $companyInfo->subscription_ends_at;
            if (is_string($endsAt)) $endsAt = Carbon::parse($endsAt);

            $subscriptionEndsAt = is_object($endsAt) && method_exists($endsAt, 'toDateTimeString')
                ? $endsAt->toDateTimeString()
                : (is_string($endsAt) ? $endsAt : null);
        }

        // ✅ Roles / Permissions
        $roles = [];
        $permissions = [];

        if (method_exists($user, 'getRoleNames')) {
            $roles = $user->getRoleNames()->values()->all();
        }

        if (method_exists($user, 'getAllPermissions')) {
            $permissions = $user->getAllPermissions()->pluck('name')->values()->all();
        }

        // ✅ Build employee payload
        $jobTitleObj = null;
        if ($employee) {
            if (method_exists($employee, 'jobTitle') && $employee->relationLoaded('jobTitle') && $employee->jobTitle) {
                $jobTitleObj = $employee->jobTitle;
            } elseif (method_exists($employee, 'job_title') && $employee->relationLoaded('job_title') && $employee->job_title) {
                $jobTitleObj = $employee->job_title;
            }
        }

        return response()->json([
            'ok' => true,
            'employee' => $employee ? [
                'id'       => $employee->id ?? null,
                'name_ar'  => $employee->name_ar ?? null,
                'name_en'  => $employee->name_en ?? null,
                'mobile'   => $employee->mobile ?? null,
                'gender'   => $employee->gender ?? null,

                'department' => (method_exists($employee, 'department') && $employee->relationLoaded('department') && $employee->department)
                    ? [
                        'id'   => $employee->department->id ?? null,
                        'name' => $employee->department->name ?? null,
                        'code' => $employee->department->code ?? null,
                    ]
                    : null,

                'job_title' => $jobTitleObj ? [
                    'id'   => $jobTitleObj->id ?? null,
                    'name' => $jobTitleObj->name ?? null,
                    'code' => $jobTitleObj->code ?? null,
                ] : null,
                'personal_photo_path' => $employee->documents->where('type', 'personal_photo')->first()?->file_path 
                    ?? $employee->personal_photo_path 
                    ?? null,
                'annual_leave_days' => $employee->annual_leave_days ?? 30,
            ] : null,

            'company' => $company ? [
                'id'                   => $company->id ?? null,
                'legal_name_ar'        => $company->legal_name_ar ?? null,
                'legal_name_en'        => $company->legal_name_en ?? null,
                'primary_domain'       => $company->primary_domain ?? null,
                'is_active'            => $company->is_active ?? null,
                'subscription_ends_at' => $subscriptionEndsAt,
                'allowed_users'        => $companyInfo?->allowed_users,
            ] : null,

            'roles'       => $roles,
            'permissions' => $permissions,

            'message' => UiMsg::toText('OK') ?? 'OK',
        ]);
    }


        public function leaveTypes(Request $request)
    {
        $user = $request->user();

        if ($resp = $this->denyIfNotMobileEmployee($user)) {
            return $resp;
        }

        $table = 'leave_policies';

        if (!Schema::hasTable($table)) {
            return response()->json([
                'ok'   => true,
                'data' => [
                    ['id' => 1, 'name' => 'Annual Leave', 'leave_type' => 'annual'],
                    ['id' => 2, 'name' => 'Sick Leave', 'leave_type' => 'sick'],
                ],
            ]);
        }

        $query = DB::table('leave_policies')
            ->join('leave_policy_years', 'leave_policies.policy_year_id', '=', 'leave_policy_years.id')
            ->where('leave_policies.is_active', true)
            ->where('leave_policies.show_in_app', true)
            ->where('leave_policy_years.is_active', true);

        // Filter by company
        if (!empty($user->saas_company_id)) {
            $query->where('leave_policies.company_id', $user->saas_company_id);
            $query->where('leave_policy_years.company_id', $user->saas_company_id);
        }

        // Filter by gender if the column exists
        $cols = Schema::getColumnListing('leave_policies');
        $employee = $user->employee ?? null;
        if (in_array('gender', $cols) && $employee && !empty($employee->gender)) {
            $gender = strtolower($employee->gender); // male / female
            $query->where(function($q) use ($gender) {
                $q->where('leave_policies.gender', 'all')
                  ->orWhere('leave_policies.gender', $gender);
            });
        }

        $types = $query->get([
            'leave_policies.id', 
            'leave_policies.name', 
            'leave_policies.leave_type', 
            'leave_policies.requires_attachment',
            'leave_policies.settings'
        ]);

        $formatted = $types->map(function($t) {
            $settings = is_string($t->settings) ? json_decode($t->settings, true) : $t->settings;
            return [
                'id' => $t->id,
                'name' => $t->name,
                'leave_type' => $t->leave_type,
                'requires_attachment' => (bool)$t->requires_attachment,
                'duration_unit' => $settings['duration_unit'] ?? 'full_day',
            ];
        });

        return response()->json([
            'ok'   => true,
            'data' => $formatted,
        ]);
    }

    public function workSchedule(Request $request)
    {
        $user = $request->user();

        if ($resp = $this->denyIfNotMobileEmployee($user)) {
            return $resp;
        }

        $employeeId = $user->employee_id;

        if (!$employeeId) {
            return response()->json(['ok' => false, 'message' => 'No employee linked'], 400);
        }

        // ── Date range ───────────────────────────────────────────────────────
        $startStr = $request->query('start', now()->startOfWeek(Carbon::SATURDAY)->toDateString());
        $endStr   = $request->query('end',   now()->endOfWeek(Carbon::FRIDAY)->toDateString());

        try {
            $start = Carbon::parse($startStr)->startOfDay();
            $end   = Carbon::parse($endStr)->endOfDay();
        } catch (\Throwable $e) {
            $start = now()->startOfWeek(Carbon::SATURDAY)->startOfDay();
            $end   = now()->endOfWeek(Carbon::FRIDAY)->endOfDay();
        }

        // Limit to max 31 days for safety
        if ($start->diffInDays($end) > 31) {
            $end = $start->copy()->addDays(30)->endOfDay();
        }

        // ── Fetch active schedule assignment ─────────────────────────────────
        $assignment = DB::table('employee_work_schedules')
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->where('start_date', '<=', $start->toDateString())
            ->where(function ($q) use ($end) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $end->toDateString());
            })
            ->orderByDesc('start_date')
            ->first();

        // ── Load schedule meta: work_days + periods ───────────────────────────
        $scheduleName = null;
        $workDaysArr  = [];   // e.g. ['saturday','sunday','monday',...]
        $periods      = [];   // shared periods for all working days

        if ($assignment) {
            $schedule = DB::table('work_schedules')
                ->where('id', $assignment->work_schedule_id)
                ->first(['name', 'work_days']);

            if ($schedule) {
                $scheduleName = $schedule->name;

                // Parse work_days JSON → normalize to lowercase strings
                $raw = $schedule->work_days;
                if (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    $workDaysArr = is_array($decoded) ? array_map('strtolower', $decoded) : [];
                } elseif (is_array($raw)) {
                    $workDaysArr = array_map('strtolower', $raw);
                }
            }

            // Periods apply to EVERY working day (no day_of_week column)
            $periodRows = DB::table('work_schedule_periods')
                ->where('work_schedule_id', $assignment->work_schedule_id)
                ->orderBy('sort_order')
                ->get(['start_time', 'end_time', 'is_night_shift']);

            foreach ($periodRows as $p) {
                $periods[] = [
                    'start_time'     => substr((string)$p->start_time, 0, 5),
                    'end_time'       => substr((string)$p->end_time, 0, 5),
                    'is_night_shift' => (bool)$p->is_night_shift,
                ];
            }
        }

        // ── Fetch approved leaves that overlap the range ──────────────────────
        // Include from_time / to_time for partial-day detection
        $leaveColumns = [
            'attendance_leave_requests.start_date',
            'attendance_leave_requests.end_date',
            'attendance_leave_requests.duration_unit',
            'leave_policies.name as leave_name',
        ];
        // Add time columns only if they exist
        $lrCols = Schema::getColumnListing('attendance_leave_requests');
        if (in_array('from_time', $lrCols)) $leaveColumns[] = 'attendance_leave_requests.from_time';
        if (in_array('to_time',   $lrCols)) $leaveColumns[] = 'attendance_leave_requests.to_time';

        $approvedLeaves = DB::table('attendance_leave_requests')
            ->join('leave_policies', 'attendance_leave_requests.leave_policy_id', '=', 'leave_policies.id')
            ->where('attendance_leave_requests.employee_id', $employeeId)
            ->where('attendance_leave_requests.status', 'approved')
            ->where('attendance_leave_requests.start_date', '<=', $end->toDateString())
            ->where('attendance_leave_requests.end_date',   '>=', $start->toDateString())
            ->get($leaveColumns);

        // Build lookup: date_string => [ {leave_name, from_time, to_time, is_full_day}, ... ]
        // A date can have multiple partial leaves
        $leaveDays = []; // date => array of leave entries
        foreach ($approvedLeaves as $leave) {
            $lStart = Carbon::parse($leave->start_date);
            $lEnd   = Carbon::parse($leave->end_date);

            $fromTime = isset($leave->from_time) ? substr((string)$leave->from_time, 0, 5) : null;
            $toTime   = isset($leave->to_time)   ? substr((string)$leave->to_time, 0, 5)   : null;
            $unit     = $leave->duration_unit ?? 'full_day';

            // It's a full-day leave if no specific times OR unit is full_day
            $isFullDay = ($unit === 'full_day') || (empty($fromTime) && empty($toTime));

            $c = $lStart->copy();
            while ($c->lte($lEnd)) {
                $dateKey = $c->toDateString();
                $leaveDays[$dateKey][] = [
                    'leave_name'  => $leave->leave_name ?? 'Leave',
                    'from_time'   => $fromTime,
                    'to_time'     => $toTime,
                    'is_full_day' => $isFullDay,
                ];
                $c->addDay();
            }
        }

        // ── Fetch approved PERMISSIONS for the range ──────────────────────────
        $permissionDays = []; // date => [{from_time, to_time, minutes}]
        if (Schema::hasTable('attendance_permission_requests')) {
            $permCols = Schema::getColumnListing('attendance_permission_requests');

            // Detect employee key column
            $permKeyCol = in_array('employee_id', $permCols, true)
                ? 'employee_id'
                : (in_array('user_id', $permCols, true) ? 'user_id' : null);

            // Detect date column
            $permDateCol = in_array('permission_date', $permCols, true)
                ? 'permission_date'
                : (in_array('date', $permCols, true) ? 'date' : null);

            if ($permKeyCol && $permDateCol) {
                // Determine the actual employee value based on key
                $permKeyVal = ($permKeyCol === 'employee_id') ? $employeeId : ($user->id ?? null);

                if ($permKeyVal) {
                    $approvedPermissions = DB::table('attendance_permission_requests')
                        ->where($permKeyCol, $permKeyVal)
                        ->where('status', 'approved')
                        ->whereBetween($permDateCol, [$start->toDateString(), $end->toDateString()])
                        ->get([$permDateCol, 'from_time', 'to_time', 'minutes']);

                    foreach ($approvedPermissions as $perm) {
                        $rawDate = $perm->{$permDateCol} ?? null;
                        if (!$rawDate) continue;
                        $dateKey = substr((string)$rawDate, 0, 10);
                        $permissionDays[$dateKey][] = [
                            'from_time' => substr((string)($perm->from_time ?? ''), 0, 5),
                            'to_time'   => substr((string)($perm->to_time ?? ''), 0, 5),
                            'minutes'   => (int)($perm->minutes ?? 0),
                        ];
                    }
                }
            }
        }

        // Helper: convert "HH:MM" to minutes-since-midnight
        $toMins = fn(string $t): int => (int)substr($t, 0, 2) * 60 + (int)substr($t, 3, 2);

        // Day-name map (Carbon: 0=Sun, 1=Mon, ..., 6=Sat)
        $dayNames = [0 => 'sunday', 1 => 'monday', 2 => 'tuesday',
                     3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday'];

        // ── Build day-by-day response ─────────────────────────────────────────
        $days = [];
        $cur  = $start->copy();

        while ($cur->lte($end)) {
            $dateStr = $cur->toDateString();
            $dayKey  = $dayNames[(int)$cur->dayOfWeek];
            $dayLeaves = $leaveDays[$dateStr] ?? [];
            $dayPermissions = $permissionDays[$dateStr] ?? [];

            // Check if ANY full-day leave covers this date
            $fullDayLeave = collect($dayLeaves)->firstWhere('is_full_day', true);

            if ($fullDayLeave) {
                // Entire day is leave
                $days[] = [
                    'date'         => $dateStr,
                    'day_key'      => $dayKey,
                    'status'       => 'on_leave',
                    'is_holiday'   => false,
                    'holiday_name' => null,
                    'is_workday'   => false,
                    'leave_name'   => $fullDayLeave['leave_name'],
                    'periods'      => [],
                    'permissions'  => $dayPermissions,
                ];
                $cur->addDay();
                continue;
            }

            // Check if it's a working day per the schedule's work_days list
            $isWorkday = !empty($workDaysArr) && in_array($dayKey, $workDaysArr, true);

            if (!$isWorkday) {
                // Off day
                $days[] = [
                    'date'         => $dateStr,
                    'day_key'      => $dayKey,
                    'status'       => 'off',
                    'is_holiday'   => false,
                    'holiday_name' => null,
                    'is_workday'   => false,
                    'leave_name'   => null,
                    'periods'      => [],
                    'permissions'  => $dayPermissions,
                ];
                $cur->addDay();
                continue;
            }

            // Working day — for each period, check if a partial leave overlaps it
            $partialLeaves = array_filter($dayLeaves, fn($l) => !$l['is_full_day'] && $l['from_time'] && $l['to_time']);
            $hasAnyLeave   = !empty($partialLeaves);

            $builtPeriods = [];
            foreach ($periods as $period) {
                $pFrom = $toMins($period['start_time']);
                $pTo   = $toMins($period['end_time']);
                // Night shift: period crosses midnight
                if ($pTo <= $pFrom) $pTo += 24 * 60;

                // Check overlap with each partial leave
                $leaveMatch = null;
                foreach ($partialLeaves as $pl) {
                    $lFrom = $toMins($pl['from_time']);
                    $lTo   = $toMins($pl['to_time']);
                    if ($lTo <= $lFrom) $lTo += 24 * 60;
                    // Overlap: leave starts before period ends AND leave ends after period starts
                    if ($lFrom < $pTo && $lTo > $pFrom) {
                        $leaveMatch = $pl;
                        break;
                    }
                }

                $builtPeriods[] = [
                    'start_time'     => $period['start_time'],
                    'end_time'       => $period['end_time'],
                    'is_night_shift' => $period['is_night_shift'],
                    'is_leave'       => $leaveMatch !== null,
                    'leave_name'     => $leaveMatch['leave_name'] ?? null,
                ];
            }

            $days[] = [
                'date'         => $dateStr,
                'day_key'      => $dayKey,
                'status'       => $hasAnyLeave ? 'partial_leave' : 'working',
                'is_holiday'   => false,
                'holiday_name' => null,
                'is_workday'   => true,
                'leave_name'   => $hasAnyLeave ? collect($partialLeaves)->first()['leave_name'] : null,
                'periods'      => $builtPeriods,
                'permissions'  => $dayPermissions,
            ];

            $cur->addDay();
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'schedule' => $assignment ? [
                    'id'   => $assignment->work_schedule_id,
                    'name' => $scheduleName,
                ] : null,
                'days' => $days,
            ],
        ]);
    }


    public function leaveRequests(Request $request)
    {
        $user = $request->user();

        // نفس شرط التطبيق: لازم يكون مرتبط بموظف
        if ($resp = $this->denyIfNotMobileEmployee($user)) {
            return $resp;
        }

        // ✅ Table name المتوقع
        $table = 'attendance_leave_requests';

        if (!Schema::hasTable($table)) {
            return response()->json([
                'ok'      => false,
                'error'   => 'table_missing',
                'message' => "Table [$table] not found.",
            ], 500);
        }

        return $this->listRequestsFromTable($request, $table, $user);
    }

    public function permissionRequests(Request $request)
    {
        $user = $request->user();

        if ($resp = $this->denyIfNotMobileEmployee($user)) {
            return $resp;
        }

        $table = 'attendance_permission_requests';

        if (!Schema::hasTable($table)) {
            return response()->json([
                'ok'      => false,
                'error'   => 'table_missing',
                'message' => "Table [$table] not found.",
            ], 500);
        }

        return $this->listRequestsFromTable($request, $table, $user);
    }

    public function missionRequests(Request $request)
    {
        $user = $request->user();

        if ($resp = $this->denyIfNotMobileEmployee($user)) {
            return $resp;
        }

        $table = 'attendance_mission_requests';

        if (!Schema::hasTable($table)) {
            return response()->json([
                'ok'      => false,
                'error'   => 'table_missing',
                'message' => "Table [$table] not found.",
            ], 500);
        }

        return $this->listRequestsFromTable($request, $table, $user);
    }

    protected function denyIfNotMobileEmployee($user)
    {
        if (! $user) {
            return response()->json([
                'ok'      => false,
                'error'   => 'unauthenticated',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ((bool) config('authkit.api.employees_only', true)) {
            $hasEmployeeId = !empty($user->employee_id);

            $employeeExists = $hasEmployeeId;
            if ($hasEmployeeId && method_exists($user, 'employee')) {
                $employeeExists = $user->employee()->exists();
            }

            if (! $employeeExists) {
                $msg = function_exists('tr')
                    ? tr('This account is not allowed to use the mobile app.')
                    : 'This account is not allowed to use the mobile app.';

                return response()->json([
                    'ok'      => false,
                    'error'   => 'not_mobile_user',
                    'message' => $msg,
                ], 403);
            }

            if ($user->getAttribute('is_active') === false) {
                $msg = function_exists('tr')
                    ? tr('Your account is currently inactive.')
                    : 'Your account is currently inactive.';

                return response()->json([
                    'ok'      => false,
                    'error'   => 'user_inactive',
                    'message' => $msg,
                ], 403);
            }
        }

        return null;
    }

    protected function listRequestsFromTable(Request $request, string $table, $user)
    {
        $cols = Schema::getColumnListing($table);

        // تحديد عمود الربط (employee_id أو user_id)
        $key = null;
        if (in_array('employee_id', $cols, true)) $key = 'employee_id';
        elseif (in_array('user_id', $cols, true)) $key = 'user_id';

        if (!$key) {
            return response()->json([
                'ok'      => false,
                'error'   => 'missing_relation_key',
                'message' => "Table [$table] missing employee_id/user_id column.",
            ], 500);
        }

        $value = ($key === 'employee_id') ? ($user->employee_id ?? null) : ($user->id ?? null);
        if (!$value) {
            return response()->json([
                'ok'      => false,
                'error'   => 'missing_employee_id',
                'message' => 'Employee ID is missing for this account.',
            ], 403);
        }

        $page    = max((int) $request->query('page', 1), 1);
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        // أعمدة شائعة (نرجع الموجود فقط)
        $wanted = [
            'id',
            $key,
            'type',
            'status',
            'reason',
            'requested_days',
            'reject_reason',
            'notes',
            'from_date', 'to_date',
            'start_date', 'end_date',
            'date',
            'permission_date',
            'from_time', 'to_time',
            'minutes',
            'destination',
            'leave_policy_id',
            'policy_year_id',
            'requested_by',
            'requested_at',
            'created_at',
            'updated_at',
        ];


        $select = array_values(array_intersect($wanted, $cols));
        if (!in_array('id', $select, true)) $select[] = 'id';

        // Prepare query with qualified names to avoid ambiguity
        // Prepare query with qualified names to avoid ambiguity
        $q = DB::table($table)->where($table . '.' . $key, $value);
        $select = array_map(fn($c) => $table . '.' . $c . ' as ' . $c, $select);

        // Add Join for Policy (Leaves only)
        if ($table === 'attendance_leave_requests') {
            $q->leftJoin('leave_policies', 'attendance_leave_requests.leave_policy_id', '=', 'leave_policies.id');
            $select[] = 'leave_policies.name as leave_type_name';
            $select[] = 'leave_policies.leave_type as leave_type';
        }

        // Add Join for Creator Name (Common for all tables with requested_by)
        if (in_array('requested_by', $cols, true)) {
            $q->leftJoin('users', $table . '.requested_by', '=', 'users.id')
              ->leftJoin('employees', 'users.employee_id', '=', 'employees.id');
            
            $select[] = 'employees.name_ar as creator_name_ar';
            $select[] = 'employees.name_en as creator_name_en';
        }

        $q->orderByDesc($table . '.id');

        // Filters اختيارية
        if (in_array('status', $cols, true) && $request->filled('status')) {
            $q->where($table . '.status', (string) $request->query('status'));
        }

        $total = (clone $q)->count();

        // If select is empty (shouldn't happen with id), fallback to all columns
        if (empty($select)) $select = ['*'];

        $items = $q->forPage($page, $perPage)
            ->get($select)
            ->values();

        // ✅ Transform to match Mobile App Model
        $locale = $request->header('Accept-Language') ?: $request->input('locale') ?: 'ar';
        if (str_contains($locale, 'ar')) $locale = 'ar';
        else $locale = 'en';

        // ✅ Fetch all approval tasks for the items in one go (Bulk Load)
        $itemIds = $items->pluck('id')->toArray();
        $approvableType = 'leaves';
        if ($table === 'attendance_permission_requests') $approvableType = 'permissions';
        if ($table === 'attendance_mission_requests')    $approvableType = 'missions';

        $allTasks = DB::table('approval_tasks')
            ->where('approvable_type', $approvableType)
            ->whereIn('approvable_id', $itemIds)
            ->orderBy('position')
            ->get();

        // Get approver employee details for these tasks
        $approverIds = $allTasks->pluck('approver_employee_id')->filter()->unique()->toArray();
        $approvers = DB::table('employees')
            ->whereIn('id', $approverIds)
            ->get()
            ->keyBy('id');

        $groupedTasks = $allTasks->groupBy('approvable_id');

        // ✅ Calculate monthly stats (Approved requests in the current month)
        $employeeId = ($key === 'employee_id') ? $value : 0;
        if ($employeeId === 0 && $key === 'user_id') {
            $employeeId = DB::table('employees')->where('user_id', $value)->value('id') ?: 0;
        }

        $currentMonth = now()->format('Y-m');
        $monthlyLeaveDays = 0;
        $monthlyPermissionMinutes = 0;

        if ($employeeId > 0) {
            $monthlyLeaveDays = DB::table('attendance_leave_requests')
                ->where('employee_id', $employeeId)
                ->where('status', 'approved')
                ->where('start_date', 'like', $currentMonth . '%')
                ->sum('requested_days');

            $monthlyPermissionMinutes = DB::table('attendance_permission_requests')
                ->where('employee_id', $employeeId)
                ->where('status', 'approved')
                ->where('permission_date', 'like', $currentMonth . '%')
                ->sum('minutes');
        }

        $transformed = $items->map(function($item) use ($table, $key, $value, $locale, $groupedTasks, $approvers, $monthlyLeaveDays, $monthlyPermissionMinutes) {
            $arr = (array)$item;
            
            // Map common fields to what Flutter expects
            if ($table === 'attendance_mission_requests') {
                $arr['leave_type'] = $locale === 'ar' ? 'مهمة عمل' : 'Work Mission';
            } else {
                $arr['leave_type'] = $arr['leave_type_name'] ?? ($table === 'attendance_permission_requests' ? ($locale === 'ar' ? 'إذن' : 'Permission') : '');
            }
            
            // Build creator name based on locale
            $nameAr = $arr['creator_name_ar'] ?? '';
            $nameEn = $arr['creator_name_en'] ?? '';
            if ($locale === 'ar') {
                $arr['creator'] = !empty($nameAr) ? $nameAr : $nameEn;
            } else {
                $arr['creator'] = !empty($nameEn) ? $nameEn : $nameAr;
            }

            $arr['request_date'] = isset($arr['created_at']) ? substr((string)$arr['created_at'], 0, 10) : '';
            $arr['requested_at'] = isset($arr['requested_at']) ? (string)$arr['requested_at'] : (isset($arr['created_at']) ? (string)$arr['created_at'] : '');
            
            // Format requested_days to remove .0 if it's integer
            if (isset($arr['requested_days'])) {
                $arr['requested_days'] = (float)$arr['requested_days'] == (int)$arr['requested_days'] 
                    ? (int)$arr['requested_days'] 
                    : (float)$arr['requested_days'];
            }

            // Dates mapping
            if (isset($arr['start_date'])) $arr['from_date'] = $arr['start_date'];
            if (isset($arr['end_date']))   $arr['to_date']   = $arr['end_date'];
            
            // Fix for missions explicitly mapping startDate/endDate if they are just start_date/end_date
            if ($table === 'attendance_mission_requests') {
                 $arr['start_date'] = $arr['start_date'] ?? '';
                 $arr['end_date']   = $arr['end_date'] ?? '';
            }
            
            if (isset($arr['permission_date'])) {
                $arr['from_date'] = $arr['permission_date'];
                $arr['to_date']   = $arr['permission_date'];
            }

            // Balance Calculation
            $balanceStr = '';
            if ($table === 'attendance_leave_requests' && !empty($arr['leave_policy_id'])) {
                $employeeId = $value;
                $policyId   = $arr['leave_policy_id'];
                $yearId     = $arr['policy_year_id'] ?? 0;

                $balanceRow = DB::table('attendance_leave_balances')
                    ->where('employee_id', $employeeId)
                    ->where('leave_policy_id', $policyId)
                    ->where('policy_year_id', $yearId)
                    ->first();
                
                if ($balanceRow) {
                    $total = (float)($balanceRow->entitled_days ?? 0);
                    $rem   = (float)($balanceRow->remaining_days ?? 0);
                    
                    $totalStr = ($total == (int)$total) ? (int)$total : $total;
                    $remStr   = ($rem == (int)$rem) ? (int)$rem : $rem;
                    
                    $balanceStr = $totalStr . ' / ' . $remStr;
                } else {
                    // Fallback Calculation
                    $policy = DB::table('leave_policies')->where('id', $policyId)->first();
                    if ($policy) {
                        $total = (float)($policy->days_per_year ?? 0);
                        $taken = DB::table('attendance_leave_requests')
                            ->where('employee_id', $employeeId)
                            ->where('leave_policy_id', $policyId)
                            ->where('policy_year_id', $yearId)
                            ->where('status', 'approved')
                            ->sum('requested_days');
                        
                        $rem = max($total - (float)$taken, 0);

                        $totalStr = ($total == (int)$total) ? (int)$total : $total;
                        $remStr   = ($rem == (int)$rem) ? (int)$rem : $rem;

                        $balanceStr = $totalStr . ' / ' . $remStr;
                    }
                }
            }
            $arr['balance'] = $balanceStr;
            
            // Monthly Stats
            $arr['monthly_taken_days'] = (float)$monthlyLeaveDays;
            $arr['monthly_taken_minutes'] = (int)$monthlyPermissionMinutes;

            // ✅ Include Approval Tasks
            $tasks = $groupedTasks->get($arr['id']) ?? collect();
            $arr['approval_tasks'] = $tasks->map(function($t) use ($approvers) {
                $tArr = (array)$t;
                $approver = $approvers->get($tArr['approver_employee_id']);
                if ($approver) {
                    $tArr['approver'] = (array)$approver;
                }
                return $tArr;
            })->values()->toArray();

            return $arr;
        });

        return response()->json([
            'ok'   => true,
            'data' => $transformed,
            'meta' => [
                'page'      => $page,
                'per_page'  => $perPage,
                'total'     => $total,
                'last_page' => (int) ceil($total / $perPage),
                'table'     => $table,
            ],
        ]);
    }
    public function createLeaveRequest(Request $request)
    {
        $user = $request->user();

        if ($resp = $this->denyIfNotMobileEmployee($user)) {
            return $resp;
        }

        $table = 'attendance_leave_requests';

        if (!Schema::hasTable($table)) {
            return response()->json([
                'ok'      => false,
                'error'   => 'table_missing',
                'message' => "Table [$table] not found.",
            ], 500);
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'reason'     => ['nullable', 'string', 'max:2000'],
            'from_time'  => ['nullable', 'date_format:H:i'],
            'to_time'    => ['nullable', 'date_format:H:i'],
            'leave_policy_id' => ['nullable', 'integer'],
        ]);

        // لو واحد موجود والثاني لا
        if ($request->filled('from_time') xor $request->filled('to_time')) {
            return response()->json([
                'ok'      => false,
                'error'   => 'invalid_time_range',
                'message' => 'from_time and to_time must be provided together.',
            ], 422);
        }

        $cols = Schema::getColumnListing($table);

        // تحديد عمود الربط (employee_id أو user_id)
        $key = in_array('employee_id', $cols, true) ? 'employee_id' : (in_array('user_id', $cols, true) ? 'user_id' : null);
        if (!$key) {
            return response()->json([
                'ok'      => false,
                'error'   => 'missing_relation_key',
                'message' => "Table [$table] missing employee_id/user_id column.",
            ], 500);
        }

        $value = ($key === 'employee_id') ? ($user->employee_id ?? null) : ($user->id ?? null);
        if (!$value) {
            return response()->json([
                'ok'      => false,
                'error'   => 'missing_employee_id',
                'message' => 'Employee ID is missing for this account.',
            ], 403);
        }

        // حساب الدقائق لو وقت موجود
        $minutes = null;
        if ($request->filled('from_time') && $request->filled('to_time')) {
            $from = Carbon::createFromFormat('H:i', $request->input('from_time'));
            $to   = Carbon::createFromFormat('H:i', $request->input('to_time'));
            $minutes = $from->diffInMinutes($to, false);
            if ($minutes <= 0) {
                return response()->json([
                    'ok'      => false,
                    'error'   => 'invalid_time_range',
                    'message' => 'to_time must be after from_time.',
                ], 422);
            }
        }

        $now = now();

        $data = [];
        $data[$key] = $value;

        $leavePolicyId = $validated['leave_policy_id'] ?? null;
        $companyId = (int) ($user->saas_company_id ?? 0);

        // Fetch policy details if ID is provided
        if ($leavePolicyId) {
            $policy = DB::table('leave_policies')
                ->where('id', $leavePolicyId)
                ->where('company_id', $companyId)
                ->first();
            
            if ($policy) {
                if (in_array('leave_policy_id', $cols, true)) {
                    $data['leave_policy_id'] = $policy->id;
                }
                if (in_array('policy_year_id', $cols, true)) {
                    $data['policy_year_id'] = $policy->policy_year_id;
                }
            }
        }

        // ✅ NEW: company_id required in some installs
        if (in_array('company_id', $cols, true)) {
            if ($companyId <= 0) {
                return response()->json([
                    'ok'      => false,
                    'error'   => 'missing_company_id',
                    'message' => 'Company ID is missing for this account.',
                ], 403);
            }
            $data['company_id'] = $companyId;
        }

        // (احتياطي) لو عندك جدول يستخدم saas_company_id بدل company_id
        if (in_array('saas_company_id', $cols, true) && empty($data['saas_company_id'])) {
            $data['saas_company_id'] = (int) ($user->saas_company_id ?? null);
        }

        if (in_array('status', $cols, true))     $data['status'] = 'pending';

        if (in_array('reason', $cols, true))     $data['reason'] = $validated['reason'] ?? '';
        if (in_array('start_date', $cols, true)) $data['start_date'] = $validated['start_date'];
        if (in_array('end_date', $cols, true))   $data['end_date'] = $validated['end_date'];

        if ($request->filled('from_time') && in_array('from_time', $cols, true)) $data['from_time'] = $validated['from_time'];
        if ($request->filled('to_time') && in_array('to_time', $cols, true))     $data['to_time'] = $validated['to_time'];
        if (!is_null($minutes) && in_array('minutes', $cols, true))              $data['minutes'] = $minutes;

        // ✅ Calculate requested_days
        if (in_array('requested_days', $cols, true)) {
            $start = Carbon::parse($validated['start_date']);
            $end = Carbon::parse($validated['end_date']);
            $data['requested_days'] = $this->computeRequestedDaysGeneric($companyId, $leavePolicyId, $start, $end);
        }

        if (in_array('source', $cols, true)) $data['source'] = 'app';
        if (in_array('requested_by', $cols, true)) $data['requested_by'] = $user->id;
        if (in_array('requested_at', $cols, true)) $data['requested_at'] = $now;

        if (in_array('created_at', $cols, true)) $data['created_at'] = $now;
        if (in_array('updated_at', $cols, true)) $data['updated_at'] = $now;

        $id = DB::table($table)->insertGetId($data);

        $row = DB::table($table)->where('id', $id)->first();

        return response()->json([
            'ok'   => true,
            'data' => $row,
        ], 201);
    }

    public function createPermissionRequest(Request $request)
    {
        $user = $request->user();

        if ($resp = $this->denyIfNotMobileEmployee($user)) {
            return $resp;
        }

        $table = 'attendance_permission_requests';

        if (!Schema::hasTable($table)) {
            return response()->json([
                'ok'      => false,
                'error'   => 'table_missing',
                'message' => "Table [$table] not found.",
            ], 500);
        }

        $validated = $request->validate([
            'permission_date' => ['nullable', 'date', 'required_without:date'],
            'date'            => ['nullable', 'date', 'required_without:permission_date'],

            'reason'    => ['nullable', 'string', 'max:2000'],
            'from_time' => ['required', 'date_format:H:i'],
            'to_time'   => ['required', 'date_format:H:i'],
        ]);


        $dateVal = (string) ($validated['permission_date'] ?? $validated['date'] ?? '');

        $from = Carbon::createFromFormat('H:i', $validated['from_time']);
        $to   = Carbon::createFromFormat('H:i', $validated['to_time']);
        $minutes = $from->diffInMinutes($to, false);

        if ($minutes <= 0) {
            return response()->json([
                'ok'      => false,
                'error'   => 'invalid_time_range',
                'message' => 'to_time must be after from_time.',
            ], 422);
        }

        $cols = Schema::getColumnListing($table);

        $key = in_array('employee_id', $cols, true) ? 'employee_id' : (in_array('user_id', $cols, true) ? 'user_id' : null);
        if (!$key) {
            return response()->json([
                'ok'      => false,
                'error'   => 'missing_relation_key',
                'message' => "Table [$table] missing employee_id/user_id column.",
            ], 500);
        }

        $value = ($key === 'employee_id') ? ($user->employee_id ?? null) : ($user->id ?? null);
        if (!$value) {
            return response()->json([
                'ok'      => false,
                'error'   => 'missing_employee_id',
                'message' => 'Employee ID is missing for this account.',
            ], 403);
        }

        $now = now();

        $data = [];
        $data[$key] = $value;

        // ✅ NEW: company_id required in some installs
        if (in_array('company_id', $cols, true)) {
            $companyId = (int) ($user->saas_company_id ?? 0);
            if ($companyId <= 0) {
                return response()->json([
                    'ok'      => false,
                    'error'   => 'missing_company_id',
                    'message' => 'Company ID is missing for this account.',
                ], 403);
            }
            $data['company_id'] = $companyId;
        }

        // (احتياطي) لو عندك جدول يستخدم saas_company_id بدل company_id
        if (in_array('saas_company_id', $cols, true) && empty($data['saas_company_id'])) {
            $data['saas_company_id'] = (int) ($user->saas_company_id ?? null);
        }

        if (in_array('status', $cols, true))   $data['status'] = 'pending';

        if (in_array('reason', $cols, true))   $data['reason'] = $validated['reason'] ?? '';

        // ✅ أهم تعديل: الجدول عندك غالباً يستخدم permission_date
        if (in_array('permission_date', $cols, true)) {
            $data['permission_date'] = $dateVal;
        } elseif (in_array('date', $cols, true)) {
            $data['date'] = $dateVal;
        }

        if (in_array('from_time', $cols, true)) $data['from_time'] = $validated['from_time'];
        if (in_array('to_time', $cols, true))   $data['to_time'] = $validated['to_time'];
        if (in_array('minutes', $cols, true))   $data['minutes'] = $minutes;

        // 🟢 NEW: Validate Limits (Daily/Monthly) based on policy
        $companyId = (int) ($user->saas_company_id ?? 0);
        $policy = null;
        if ($companyId > 0 && Schema::hasTable('permission_policies')) {
            $permCols = Schema::getColumnListing('permission_policies');
            $permQuery = DB::table('permission_policies')->where('company_id', $companyId);
            // Only filter by policy_year_id if we can find the active year
            if (in_array('policy_year_id', $permCols, true) && class_exists(\Athka\SystemSettings\Models\LeavePolicyYear::class)) {
                $yearId = (int) \Athka\SystemSettings\Models\LeavePolicyYear::query()
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->value('id');
                if ($yearId > 0) {
                    $permQuery->where('policy_year_id', $yearId);
                }
            }
            $policy = $permQuery->first();
        }

        if ($policy) {
            // 1. Max per request limit
            if ($policy->max_request_minutes > 0 && $minutes > $policy->max_request_minutes) {
                return response()->json([
                    'ok'      => false,
                    'error'   => 'limit_exceeded',
                    'message' => 'تجاوزت الحد المسموح به للطلب الواحد (' . $policy->max_request_minutes . ' دقيقة).',
                ], 422);
            }

            // 2. Monthly limit
            if ($policy->monthly_limit_minutes > 0) {
                $monthStart = Carbon::parse($dateVal)->startOfMonth()->toDateString();
                $monthEnd   = Carbon::parse($dateVal)->endOfMonth()->toDateString();

                $usedMinutes = DB::table($table)
                    ->where($key, $value)
                    ->whereBetween('permission_date', [$monthStart, $monthEnd])
                    ->whereIn('status', ['approved', 'pending'])
                    ->sum('minutes');

                if (($usedMinutes + $minutes) > $policy->monthly_limit_minutes) {
                    // Check if policy allows exceeding
                    $settings = is_string($policy->settings) ? json_decode($policy->settings, true) : ($policy->settings ?? []);
                    $allowExceed = (bool)($settings['allow_exceed_monthly_limit'] ?? false);

                    if (!$allowExceed) {
                        return response()->json([
                            'ok'      => false,
                            'error'   => 'monthly_limit_exceeded',
                            'message' => 'تجاوزت الرصيد الشهري المسموح به للاستئذان (' . $policy->monthly_limit_minutes . ' دقيقة).',
                        ], 422);
                    }
                }
            }
        }

        if (in_array('requested_by', $cols, true)) $data['requested_by'] = $user->id;
        if (in_array('requested_at', $cols, true)) $data['requested_at'] = $now;

        if (in_array('created_at', $cols, true)) $data['created_at'] = $now;
        if (in_array('updated_at', $cols, true)) $data['updated_at'] = $now;

        $id = DB::table($table)->insertGetId($data);

        $row = DB::table($table)->where('id', $id)->first();

        return response()->json([
            'ok'   => true,
            'data' => $row,
        ], 201);
    }

    protected function computeRequestedDaysGeneric($companyId, $leavePolicyId, Carbon $start, Carbon $end): float
    {
        $policy = $leavePolicyId ? DB::table('leave_policies')->where('id', $leavePolicyId)->first() : null;
        $settings = $policy ? (is_string($policy->settings) ? json_decode($policy->settings, true) : $policy->settings) : [];
        $weekendPolicy = (string) ($settings['weekend_policy'] ?? 'exclude');
        
        $workingDays = $this->getCompanyWorkingDays($companyId);
        
        $holidays = DB::table('official_holiday_occurrences')
            ->where('company_id', $companyId)
            ->where(function($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()]);
            })
            ->get();

        // Check if it's a partial day (same day with times)
        $request = request();
        if ($start->isSameDay($end) && $request->filled('from_time') && $request->filled('to_time')) {
            $from = Carbon::createFromFormat('H:i', $request->input('from_time'));
            $to   = Carbon::createFromFormat('H:i', $request->input('to_time'));
            $diffMins = $from->diffInMinutes($to);
            // We assume a standard work day is 8 hours (480 mins) for the fraction
            // You can adjust this based on policy if needed
            return round($diffMins / 480, 2);
        }

        $days = 0.0;
        $cursor = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($cursor->lte($endDay)) {
            $isHoliday = $holidays->contains(fn($h) => $cursor->between($h->start_date, $h->end_date));
            if ($isHoliday) {
                $cursor->addDay();
                continue;
            }

            if ($weekendPolicy === 'include' || in_array((int)$cursor->dayOfWeek, $workingDays, true)) {
                $days += 1.0;
            }
            $cursor->addDay();
        }

        return $days;
    }

    public function updateLeaveRequest(Request $request, $id)
    {
        $user = $request->user();

        if ($resp = $this->denyIfNotMobileEmployee($user)) {
            return $resp;
        }

        $table = 'attendance_leave_requests';

        if (!Schema::hasTable($table)) {
            return response()->json([
                'ok'      => false,
                'error'   => 'table_missing',
                'message' => "Table [$table] not found.",
            ], 500);
        }

        $cols = Schema::getColumnListing($table);
        $key = in_array('employee_id', $cols, true) ? 'employee_id' : (in_array('user_id', $cols, true) ? 'user_id' : null);
        
        $value = ($key === 'employee_id') ? ($user->employee_id ?? null) : ($user->id ?? null);
        
        $leaveRequest = DB::table($table)->where('id', $id)->where($key, $value)->first();

        if (!$leaveRequest) {
            return response()->json([
                'ok'      => false,
                'error'   => 'not_found',
                'message' => 'Request not found.',
            ], 404);
        }

        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'ok'      => false,
                'error'   => 'cannot_update',
                'message' => 'Cannot update a request that is not pending.',
            ], 400);
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'reason'     => ['nullable', 'string', 'max:2000'],
            'from_time'  => ['nullable', 'date_format:H:i'],
            'to_time'    => ['nullable', 'date_format:H:i'],
            'leave_policy_id' => ['nullable', 'integer'],
        ]);

        if ($request->filled('from_time') xor $request->filled('to_time')) {
            return response()->json([
                'ok'      => false,
                'error'   => 'invalid_time_range',
                'message' => 'from_time and to_time must be provided together.',
            ], 422);
        }

        $minutes = null;
        if ($request->filled('from_time') && $request->filled('to_time')) {
            $from = Carbon::createFromFormat('H:i', $request->input('from_time'));
            $to   = Carbon::createFromFormat('H:i', $request->input('to_time'));
            $minutes = $from->diffInMinutes($to, false);
            if ($minutes <= 0) {
                return response()->json([
                    'ok'      => false,
                    'error'   => 'invalid_time_range',
                    'message' => 'to_time must be after from_time.',
                ], 422);
            }
        }

        $now = now();
        $data = [];
        
        $leavePolicyId = $validated['leave_policy_id'] ?? $leaveRequest->leave_policy_id;
        $companyId = (int) ($user->saas_company_id ?? 0);

        if (isset($validated['leave_policy_id'])) {
            $policy = DB::table('leave_policies')
                ->where('id', $leavePolicyId)
                ->where('company_id', $companyId)
                ->first();
            
            if ($policy) {
                if (in_array('leave_policy_id', $cols, true)) $data['leave_policy_id'] = $policy->id;
                if (in_array('policy_year_id', $cols, true)) $data['policy_year_id'] = $policy->policy_year_id;
            }
        }

        if (array_key_exists('reason', $validated) && in_array('reason', $cols, true)) $data['reason'] = $validated['reason'];
        if (in_array('start_date', $cols, true)) $data['start_date'] = $validated['start_date'];
        if (in_array('end_date', $cols, true))   $data['end_date'] = $validated['end_date'];

        if ($request->filled('from_time') && in_array('from_time', $cols, true)) $data['from_time'] = $validated['from_time'];
        if ($request->filled('to_time') && in_array('to_time', $cols, true))     $data['to_time'] = $validated['to_time'];
        if (!is_null($minutes) && in_array('minutes', $cols, true))              $data['minutes'] = $minutes;

        if (in_array('requested_days', $cols, true)) {
            $start = Carbon::parse($validated['start_date']);
            $end = Carbon::parse($validated['end_date']);
            $data['requested_days'] = $this->computeRequestedDaysGeneric($companyId, $leavePolicyId, $start, $end);
        }

        if (in_array('updated_at', $cols, true)) $data['updated_at'] = $now;

        DB::table($table)->where('id', $id)->update($data);

        return response()->json([
            'ok'   => true,
            'message' => 'Request updated successfully.',
            'data' => DB::table($table)->where('id', $id)->first(),
        ]);
    }

    public function deleteLeaveRequest(Request $request, $id)
    {
        $user = $request->user();

        if ($resp = $this->denyIfNotMobileEmployee($user)) {
            return $resp;
        }

        $table = 'attendance_leave_requests';
        $cols = Schema::getColumnListing($table);
        $key = in_array('employee_id', $cols, true) ? 'employee_id' : (in_array('user_id', $cols, true) ? 'user_id' : null);
        $value = ($key === 'employee_id') ? ($user->employee_id ?? null) : ($user->id ?? null);
        
        $leaveRequest = DB::table($table)->where('id', $id)->where($key, $value)->first();

        if (!$leaveRequest) {
            return response()->json([
                'ok'      => false,
                'error'   => 'not_found',
                'message' => 'Request not found.',
            ], 404);
        }

        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'ok'      => false,
                'error'   => 'cannot_delete',
                'message' => 'Cannot delete a request that is not pending.',
            ], 400);
        }

        DB::table($table)->where('id', $id)->delete();

        return response()->json([
            'ok'   => true,
            'message' => 'Request deleted successfully.',
        ]);
    }

    public function updatePermissionRequest(Request $request, $id)
    {
        $user = $request->user();

        if ($resp = $this->denyIfNotMobileEmployee($user)) {
            return $resp;
        }

        $table = 'attendance_permission_requests';
        $cols = Schema::getColumnListing($table);
        $key = in_array('employee_id', $cols, true) ? 'employee_id' : (in_array('user_id', $cols, true) ? 'user_id' : null);
        $value = ($key === 'employee_id') ? ($user->employee_id ?? null) : ($user->id ?? null);

        $permissionRequest = DB::table($table)->where('id', $id)->where($key, $value)->first();

        if (!$permissionRequest) {
            return response()->json([
                'ok'      => false,
                'error'   => 'not_found',
                'message' => 'Request not found.',
            ], 404);
        }

        if ($permissionRequest->status !== 'pending') {
            return response()->json([
                'ok'      => false,
                'error'   => 'cannot_update',
                'message' => 'Cannot update a request that is not pending.',
            ], 400);
        }

        $validated = $request->validate([
            'permission_date' => ['nullable', 'date', 'required_without:date'],
            'date'            => ['nullable', 'date', 'required_without:permission_date'],
            'reason'          => ['nullable', 'string', 'max:2000'],
            'from_time'       => ['required', 'date_format:H:i'],
            'to_time'         => ['required', 'date_format:H:i'],
        ]);

        $dateVal = (string) ($validated['permission_date'] ?? $validated['date'] ?? '');
        $from = Carbon::createFromFormat('H:i', $validated['from_time']);
        $to   = Carbon::createFromFormat('H:i', $validated['to_time']);
        $minutes = $from->diffInMinutes($to, false);

        if ($minutes <= 0) {
            return response()->json([
                'ok'      => false,
                'error'   => 'invalid_time_range',
                'message' => 'to_time must be after from_time.',
            ], 422);
        }

        $data = [];
        if (array_key_exists('reason', $validated) && in_array('reason', $cols, true)) $data['reason'] = $validated['reason'];
        
        if (in_array('permission_date', $cols, true)) {
            $data['permission_date'] = $dateVal;
        } elseif (in_array('date', $cols, true)) {
            $data['date'] = $dateVal;
        }

        if (in_array('from_time', $cols, true)) $data['from_time'] = $validated['from_time'];
        if (in_array('to_time', $cols, true))   $data['to_time'] = $validated['to_time'];
        if (in_array('minutes', $cols, true))   $data['minutes'] = $minutes;
        if (in_array('updated_at', $cols, true)) $data['updated_at'] = now();

        // 🟢 NEW: Validate Limits (Daily/Monthly) based on policy
        $companyId = (int) ($user->saas_company_id ?? 0);
        $policy = null;
        if ($companyId > 0 && Schema::hasTable('permission_policies')) {
            $permCols = Schema::getColumnListing('permission_policies');
            $permQuery = DB::table('permission_policies')->where('company_id', $companyId);
            if (in_array('policy_year_id', $permCols, true) && class_exists(\Athka\SystemSettings\Models\LeavePolicyYear::class)) {
                $yearId = (int) \Athka\SystemSettings\Models\LeavePolicyYear::query()
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->value('id');
                if ($yearId > 0) {
                    $permQuery->where('policy_year_id', $yearId);
                }
            }
            $policy = $permQuery->first();
        }

        if ($policy) {
            if ($policy->max_request_minutes > 0 && $minutes > $policy->max_request_minutes) {
                return response()->json([
                    'ok'      => false,
                    'error'   => 'limit_exceeded',
                    'message' => 'تجاوزت الحد المسموح به للطلب الواحد (' . $policy->max_request_minutes . ' دقيقة).',
                ], 422);
            }

            if ($policy->monthly_limit_minutes > 0) {
                $monthStart = Carbon::parse($dateVal)->startOfMonth()->toDateString();
                $monthEnd   = Carbon::parse($dateVal)->endOfMonth()->toDateString();

                $usedMinutes = DB::table($table)
                    ->where($key, $value)
                    ->where('id', '!=', $id) // Exclude current request
                    ->whereBetween('permission_date', [$monthStart, $monthEnd])
                    ->whereIn('status', ['approved', 'pending'])
                    ->sum('minutes');

                if (($usedMinutes + $minutes) > $policy->monthly_limit_minutes) {
                    $settings = is_string($policy->settings) ? json_decode($policy->settings, true) : ($policy->settings ?? []);
                    $allowExceed = (bool)($settings['allow_exceed_monthly_limit'] ?? false);

                    if (!$allowExceed) {
                        return response()->json([
                            'ok'      => false,
                            'error'   => 'monthly_limit_exceeded',
                            'message' => 'تجاوزت الرصيد الشهري المسموح به للاستئذان (' . $policy->monthly_limit_minutes . ' دقيقة).',
                        ], 422);
                    }
                }
            }
        }

        DB::table($table)->where('id', $id)->update($data);

        return response()->json([
            'ok'   => true,
            'message' => 'Request updated successfully.',
            'data' => DB::table($table)->where('id', $id)->first(),
        ]);
    }

    public function deletePermissionRequest(Request $request, $id)
    {
        $user = $request->user();

        if ($resp = $this->denyIfNotMobileEmployee($user)) {
            return $resp;
        }

        $table = 'attendance_permission_requests';
        $cols = Schema::getColumnListing($table);
        $key = in_array('employee_id', $cols, true) ? 'employee_id' : (in_array('user_id', $cols, true) ? 'user_id' : null);
        $value = ($key === 'employee_id') ? ($user->employee_id ?? null) : ($user->id ?? null);

        $permissionRequest = DB::table($table)->where('id', $id)->where($key, $value)->first();

        if (!$permissionRequest) {
            return response()->json([
                'ok'      => false,
                'error'   => 'not_found',
                'message' => 'Request not found.',
            ], 404);
        }

        if ($permissionRequest->status !== 'pending') {
            return response()->json([
                'ok'      => false,
                'error'   => 'cannot_delete',
                'message' => 'Cannot delete a request that is not pending.',
            ], 400);
        }

        DB::table($table)->where('id', $id)->delete();

        return response()->json([
            'ok'   => true,
            'message' => 'Request deleted successfully.',
        ]);
    }

    public function createMissionRequest(Request $request)
    {
        $user = $request->user();

        if ($resp = $this->denyIfNotMobileEmployee($user)) {
            return $resp;
        }

        $table = 'attendance_mission_requests';

        if (!Schema::hasTable($table)) {
            return response()->json([
                'ok'      => false,
                'error'   => 'table_missing',
                'message' => "Table [$table] not found.",
            ], 500);
        }

        $validated = $request->validate([
            'type'            => ['required', 'in:full_day,partial'],
            'start_date'      => ['required', 'date'],
            'end_date'        => ['nullable', 'date'],
            'from_time'       => ['nullable', 'date_format:H:i'],
            'to_time'         => ['nullable', 'date_format:H:i'],
            'destination'     => ['nullable', 'string', 'max:255'],
            'reason'          => ['nullable', 'string', 'max:2000'],
        ]);

        $employeeId = (int) ($user->employee_id ?? null);
        $companyId = (int) ($user->saas_company_id ?? 0);

        if (!$employeeId) {
            return response()->json(['ok' => false, 'error' => 'missing_employee_id'], 403);
        }

        $data = [
            'company_id'    => $companyId,
            'employee_id'   => $employeeId,
            'type'          => $validated['type'],
            'start_date'    => $validated['start_date'],
            'end_date'      => $validated['end_date'] ?? $validated['start_date'],
            'from_time'     => $validated['from_time'] ?? null,
            'to_time'       => $validated['to_time'] ?? null,
            'destination'   => $validated['destination'] ?? '',
            'reason'        => $validated['reason'] ?? '',
            'status'        => 'pending',
            'requested_by'  => $user->id,
            'requested_at'  => now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ];

        $id = DB::table($table)->insertGetId($data);

        // ✅ NEW: Trigger lazy task generation immediately so it shows up in "My Missions"
        try {
            if (class_exists('Athka\SystemSettings\Http\Controllers\Api\Employee\ApprovalInboxController')) {
                 $inbox = new \Athka\SystemSettings\Http\Controllers\Api\Employee\ApprovalInboxController();
                 // We need to use reflection or just copy the logic if it's private.
                 // Actually, let's just use the DB to trigger it if there's no better way,
                 // or just copy the essential logic here.
                 $this->ensureMissionTasks($id, $employeeId, $companyId);
            }
        } catch (\Throwable $e) {
            // Log or ignore if settings module not available
        }

        return response()->json([
            'ok'      => true,
            'id'      => $id,
            'message' => 'Mission request created successfully.',
        ]);
    }

    public function updateMissionRequest(Request $request, $id)
    {
        $user = $request->user();

        if ($resp = $this->denyIfNotMobileEmployee($user)) {
            return $resp;
        }

        $table = 'attendance_mission_requests';
        $missionRequest = DB::table($table)->where('id', $id)->first();

        if (!$missionRequest) {
            return response()->json([
                'ok'      => false,
                'error'   => 'not_found',
                'message' => 'Request not found.',
            ], 404);
        }

        if ($missionRequest->employee_id != $user->employee_id) {
             return response()->json([
                'ok'      => false,
                'error'   => 'unauthorized',
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($missionRequest->status !== 'pending') {
            return response()->json([
                'ok'      => false,
                'error'   => 'cannot_update',
                'message' => 'Cannot update a request that is not pending.',
            ], 400);
        }

        $validated = $request->validate([
            'type'        => ['required', 'in:full_day,partial'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required_if:type,full_day', 'nullable', 'date'],
            'from_time'   => ['required_if:type,partial', 'nullable', 'date_format:H:i'],
            'to_time'     => ['required_if:type,partial', 'nullable', 'date_format:H:i'],
            'destination' => ['nullable', 'string', 'max:500'],
            'reason'      => ['nullable', 'string', 'max:2000'],
        ]);

        $data = [
            'type'          => $validated['type'],
            'start_date'    => $validated['start_date'],
            'end_date'      => $validated['type'] === 'full_day' ? ($validated['end_date'] ?? $validated['start_date']) : $validated['start_date'],
            'from_time'     => $validated['type'] === 'partial' ? $validated['from_time'] : null,
            'to_time'       => $validated['type'] === 'partial' ? $validated['to_time'] : null,
            'destination'   => $validated['destination'] ?? '',
            'reason'        => $validated['reason'] ?? '',
            'updated_at'    => now(),
        ];

        DB::table($table)->where('id', $id)->update($data);

        return response()->json([
            'ok'   => true,
            'message' => 'Mission request updated successfully.',
        ]);
    }

    /**
     * ✅ Helper to ensure tasks for mission request on creation
     */
    protected function ensureMissionTasks($id, $employeeId, $companyId)
    {
        // Copy logic from ApprovalInboxController to avoid private access issues
        $policy = DB::table('approval_policies')
            ->where('company_id', $companyId)
            ->where('operation_key', 'missions')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if (!$policy) return;

        $steps = DB::table('approval_policy_steps')
            ->where('policy_id', $policy->id)
            ->orderBy('position')
            ->get();

        $firstPendingDone = false;
        foreach ($steps as $s) {
            $approverId = 0;
            if ($s->approver_type === 'user') {
                $approverId = (int) $s->approver_id;
            } else {
                // direct_manager logic
                $approverId = DB::table('employees')->where('id', $employeeId)->value('manager_id');
            }

            $status = 'waiting';
            if ($approverId > 0 && !$firstPendingDone) {
                $status = 'pending';
                $firstPendingDone = true;
            } elseif ($approverId <= 0) {
                $status = 'skipped';
            }

            DB::table('approval_tasks')->insert([
                'company_id' => $companyId,
                'operation_key' => 'missions',
                'approvable_type' => 'missions',
                'approvable_id' => $id,
                'request_employee_id' => $employeeId,
                'position' => $s->position,
                'approver_employee_id' => $approverId ?: null,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function deleteMissionRequest(Request $request, $id)
    {
        $user = $request->user();

        if ($resp = $this->denyIfNotMobileEmployee($user)) {
            return $resp;
        }

        $table = 'attendance_mission_requests';

        if (!Schema::hasTable($table)) {
            return response()->json([
                'ok'      => false,
                'error'   => 'table_missing',
                'message' => "Table [$table] not found.",
            ], 500);
        }

        $mission = DB::table($table)->where('id', $id)->first();

        if (!$mission) {
            return response()->json([
                'ok'      => false,
                'error'   => 'not_found',
                'message' => 'Mission request not found.',
            ], 404);
        }

        if ($mission->status !== 'pending') {
            return response()->json([
                'ok'      => false,
                'error'   => 'cannot_delete',
                'message' => 'Cannot delete a request that is not pending.',
            ], 400);
        }

        DB::table($table)->where('id', $id)->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Mission request deleted successfully.',
        ]);
    }

    protected function getCompanyWorkingDays($companyId): array
    {
        $row = DB::table('operational_calendars')
            ->where('company_id', $companyId)
            ->first();

        $days = $row ? (is_string($row->working_days) ? json_decode($row->working_days, true) : $row->working_days) : null;
        
        if ($days && is_array($days)) {
             $out = [];
             $map = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
             foreach($days as $d) {
                 if (is_numeric($d)) $out[] = (int)$d;
                 else {
                     $k = strtolower(trim((string)$d));
                     if (isset($map[$k])) $out[] = $map[$k];
                 }
             }
             if (!empty($out)) return array_values(array_unique($out));
        }

        return [6, 0, 1, 2, 3]; // Default fallback
    }
}
