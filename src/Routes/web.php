<?php

use Illuminate\Support\Facades\Route;
use Athka\Employees\Livewire\Employees\Index;
use Athka\Employees\Livewire\Employees\Create;
use Athka\Employees\Livewire\Employees\Edit;

Route::get('/documents/{document}/file', function (\Athka\Employees\Models\EmployeeDocument $document) {
    $user = request()->user();
    abort_unless($user, 403);

    $employee = $document->employee()->withoutGlobalScopes()->first();
    abort_unless($employee, 404);

    $isSaasAdmin = method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['saas-admin']);
    $isCompanyAdmin = method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['company-admin']);
    $sameCompany = (int) ($employee->saas_company_id ?? 0) === (int) ($user->saas_company_id ?? 0);
    $isSelf = (int) ($user->employee_id ?? 0) === (int) $employee->id;
    $canReadDocuments = $isCompanyAdmin
        || $user->can('employees.documents.manage')
        || $user->can('employees.view-details')
        || $user->can('employees.edit')
        || $user->can('employees.view');
    $hasBroadEmployeeAccess = $isCompanyAdmin || $user->can('employees.view.all');
    $hasScopedEmployeeAccess = $isSelf
        || ((int) ($user->employee_id ?? 0) > 0 && (int) ($employee->manager_id ?? 0) === (int) $user->employee_id)
        || ((int) ($user->department_id ?? 0) > 0 && (int) ($employee->department_id ?? 0) === (int) $user->department_id);

    abort_unless(
        $isSaasAdmin || ($sameCompany && ($isSelf || ($canReadDocuments && ($hasBroadEmployeeAccess || $hasScopedEmployeeAccess)))),
        403
    );

    $basePath = realpath(storage_path('app/public'));
    $requestedPath = str_replace(['\\', '//'], '/', ltrim((string) $document->file_path, '/\\'));
    $fullPath = $basePath ? realpath($basePath.DIRECTORY_SEPARATOR.$requestedPath) : false;

    if (! $basePath || ! $fullPath || ! is_file($fullPath)) {
        abort(404);
    }

    $basePrefix = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    if (! str_starts_with($fullPath, $basePrefix)) {
        abort(404);
    }

    return response()->file($fullPath);
})->name('documents.file');
Route::get('/leave-adjustments/{adjustment}/file', function (\Athka\Employees\Models\EmployeeLeaveAdjustment $adjustment) {
    $user = request()->user();
    abort_unless($user, 403);

    $employee = $adjustment->employee()->withoutGlobalScopes()->first();
    abort_unless($employee, 404);

    $isSaasAdmin = method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['saas-admin']);
    $isCompanyAdmin = method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['company-admin']);
    $sameCompany = (int) ($employee->saas_company_id ?? 0) === (int) ($user->saas_company_id ?? 0);
    $canReadFile = $isCompanyAdmin || $user->can('employees.contracts.manage');
    $hasBroadEmployeeAccess = $isCompanyAdmin || $user->can('employees.view.all');
    $hasScopedEmployeeAccess = ((int) ($user->employee_id ?? 0) > 0 && (int) ($employee->manager_id ?? 0) === (int) $user->employee_id)
        || ((int) ($user->department_id ?? 0) > 0 && (int) ($employee->department_id ?? 0) === (int) $user->department_id);

    abort_unless(
        $isSaasAdmin || ($sameCompany && $canReadFile && ($hasBroadEmployeeAccess || $hasScopedEmployeeAccess)),
        403
    );

    $basePath = realpath(storage_path('app/public'));
    $requestedPath = str_replace(['\\', '//'], '/', ltrim((string) $adjustment->file_path, '/\\'));
    $fullPath = $basePath ? realpath($basePath.DIRECTORY_SEPARATOR.$requestedPath) : false;

    if (! $basePath || ! $fullPath || ! is_file($fullPath)) {
        abort(404);
    }

    $basePrefix = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    if (! str_starts_with($fullPath, $basePrefix)) {
        abort(404);
    }

    return response()->file($fullPath);
})->name('leave-adjustments.file');
Route::get('/', Index::class)->name('index');
Route::get('/create', Create::class)->name('create');
Route::get('/{employeeId}/edit', Edit::class)->name('edit');




