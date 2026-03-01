<?php

use Illuminate\Support\Facades\Route;
use Athka\Employees\Http\Controllers\Api\EmployeeController;

Route::middleware(['api', 'auth:sanctum'])
    ->prefix('api/employee')
    ->as('employees.api.')
    ->group(function () {
        Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
        Route::get('/work-schedule', [EmployeeController::class, 'workSchedule'])->name('work_schedule');
        Route::get('/leave-types', [EmployeeController::class, 'leaveTypes'])->name('leave_types');
        Route::get('/leave-requests', [EmployeeController::class, 'leaveRequests'])->name('leave_requests');
        Route::post('/leave-requests', [EmployeeController::class, 'createLeaveRequest'])->name('leave_requests.create');
        Route::put('/leave-requests/{id}', [EmployeeController::class, 'updateLeaveRequest'])->name('leave_requests.update');
        Route::delete('/leave-requests/{id}', [EmployeeController::class, 'deleteLeaveRequest'])->name('leave_requests.delete');

        Route::get('/permission-requests', [EmployeeController::class, 'permissionRequests'])->name('permission_requests');
        Route::post('/permission-requests', [EmployeeController::class, 'createPermissionRequest'])->name('permission_requests.create');
        Route::put('/permission-requests/{id}', [EmployeeController::class, 'updatePermissionRequest'])->name('permission_requests.update');
        Route::delete('/permission-requests/{id}', [EmployeeController::class, 'deletePermissionRequest'])->name('permission_requests.delete');

        Route::get('/mission-requests', [EmployeeController::class, 'missionRequests'])->name('mission_requests');
        Route::post('/mission-requests', [EmployeeController::class, 'createMissionRequest'])->name('mission_requests.create');
        Route::put('/mission-requests/{id}', [EmployeeController::class, 'updateMissionRequest'])->name('mission_requests.update');
        Route::delete('/mission-requests/{id}', [EmployeeController::class, 'deleteMissionRequest'])->name('mission_requests.delete');
    });

