<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Index');
})->name('home');

// Project Registration (Inertia wizard)
Route::get('/projects/register', [\App\Http\Controllers\ProjectRegistrationController::class, 'create'])->name('project.register.create');
Route::post('/projects/register/finalize', [\App\Http\Controllers\ProjectRegistrationController::class, 'finalize'])->name('project.register.finalize');

// Admin Reports
Route::get('/admin/projects', [\App\Http\Controllers\Admin\ProjectReportController::class, 'index'])->name('admin.projects.index');
Route::get('/admin/projects/export/excel', [\App\Http\Controllers\Admin\ProjectReportController::class, 'exportExcel'])->name('admin.projects.export.excel');
Route::get('/admin/projects/export/pdf', [\App\Http\Controllers\Admin\ProjectReportController::class, 'exportPdf'])->name('admin.projects.export.pdf');