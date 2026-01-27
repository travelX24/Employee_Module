<?php

use Illuminate\Support\Facades\Route;
use Athka\Employees\Livewire\Employees\Index;
use Athka\Employees\Livewire\Employees\Create;
use Athka\Employees\Livewire\Employees\Edit;

Route::get('/', Index::class)->name('index');
Route::get('/create', Create::class)->name('create');
Route::get('/{employeeId}/edit', Edit::class)->name('edit');




