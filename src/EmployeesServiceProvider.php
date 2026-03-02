<?php

namespace Athka\Employees;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class EmployeesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ✅ Views: employees::*
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'employees');

        // ✅ Migrations
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        
        $this->loadRoutesFrom(__DIR__ . '/Routes/api.php'); // ✅ NEW

        // ✅ Routes (مرة واحدة فقط)
        Route::middleware([
            'web',
            'auth',
            \Athka\Saas\Http\Middleware\EnsureCompanyAdmin::class,
            \Athka\Saas\Http\Middleware\ForceCompanyDomain::class,
            'company.domain',
            \Athka\Saas\Http\Middleware\SetCompanyTimezone::class,
        ])
            ->prefix('employees')
            ->name('company-admin.employees.')
            ->group(__DIR__ . '/Routes/web.php');

        // ✅ Livewire: تسجيل يدوي للمكونات لضمان عملها بشكل صحيح داخل الموديول
        \Livewire\Livewire::component('employees.index', \Athka\Employees\Livewire\Employees\Index::class);
        \Livewire\Livewire::component('employees.create', \Athka\Employees\Livewire\Employees\Create::class);
        \Livewire\Livewire::component('employees.edit', \Athka\Employees\Livewire\Employees\Edit::class);
        \Livewire\Livewire::component('employees.detail-modal', \Athka\Employees\Livewire\Employees\DetailModal::class);
    }
}




