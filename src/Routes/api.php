<?php

use Illuminate\Support\Facades\Route;
use Athka\Employees\Http\Controllers\Api\EmployeeController;

Route::middleware(['api', 'auth:sanctum'])
    ->prefix('api/employee')
    ->as('employees.api.')
    ->group(function () {
        Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
        Route::get('/leave-requests', [EmployeeController::class, 'leaveRequests'])->name('leave_requests');
        Route::get('/permission-requests', [EmployeeController::class, 'permissionRequests'])->name('permission_requests');

        Route::post('/leave-requests', [EmployeeController::class, 'createLeaveRequest'])->name('leave_requests.create');
        Route::post('/permission-requests', [EmployeeController::class, 'createPermissionRequest'])->name('permission_requests.create');
   
    });

