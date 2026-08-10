<?php

namespace Athka\Employees\Observers;

use Illuminate\Support\Facades\Cache;
use Athka\Employees\Models\Employee;

/**
 * Observer: يمسح كاش قوائم الموظفين/المديرين فور أي تغيير.
 * هذا يضمن ظهور المديرين الجدد والتغييرات فوراً في فلاتر الصفحة.
 */
class EmployeeCacheObserver
{
    private function clearCache(Employee $employee): void
    {
        $companyId = $employee->saas_company_id;

        Cache::forget("managers_options_{$companyId}");
        Cache::increment("employees:cache-version:{$companyId}");
    }

    public function saved(Employee $employee): void
    {
        $this->clearCache($employee);
    }

    public function deleted(Employee $employee): void
    {
        $this->clearCache($employee);
    }

    public function restored(Employee $employee): void
    {
        $this->clearCache($employee);
    }
}
