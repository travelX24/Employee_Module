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
            'status',
            'reason',
            'notes',
            'from_date', 'to_date',
            'start_date', 'end_date',
            'date',
            'permission_date', // ✅ NEW
            'from_time', 'to_time',
            'minutes',
            'created_at',
            'updated_at',
        ];


        $select = array_values(array_intersect($wanted, $cols));
        if (!in_array('id', $select, true)) $select[] = 'id';

        $q = DB::table($table)
            ->where($key, $value)
            ->orderByDesc('id');

        // Filters اختيارية
        if (in_array('status', $cols, true) && $request->filled('status')) {
            $q->where('status', (string) $request->query('status'));
        }

        $total = (clone $q)->count();

        $items = $q->forPage($page, $perPage)
            ->get($select)
            ->values();

        return response()->json([
            'ok'   => true,
            'data' => $items,
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

    if (in_array('status', $cols, true))     $data['status'] = 'pending';

    if (in_array('reason', $cols, true))     $data['reason'] = $validated['reason'] ?? '';
    if (in_array('start_date', $cols, true)) $data['start_date'] = $validated['start_date'];
    if (in_array('end_date', $cols, true))   $data['end_date'] = $validated['end_date'];

    if ($request->filled('from_time') && in_array('from_time', $cols, true)) $data['from_time'] = $validated['from_time'];
    if ($request->filled('to_time') && in_array('to_time', $cols, true))     $data['to_time'] = $validated['to_time'];
    if (!is_null($minutes) && in_array('minutes', $cols, true))              $data['minutes'] = $minutes;

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


    if (in_array('created_at', $cols, true)) $data['created_at'] = $now;
    if (in_array('updated_at', $cols, true)) $data['updated_at'] = $now;

    $id = DB::table($table)->insertGetId($data);

    $row = DB::table($table)->where('id', $id)->first();

    return response()->json([
        'ok'   => true,
        'data' => $row,
    ], 201);
}


}
