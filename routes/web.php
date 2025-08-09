<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProjectRegistrationController;
use App\Http\Controllers\Admin\ProjectReportController;


Route::get('/', function () {
    return Inertia::render('Index');
})->name('home');

// Project Registration (Inertia wizard)
Route::get('/projects/register', [ProjectRegistrationController::class, 'create'])->name('project.register.create');
Route::post('/projects/register/finalize', [ProjectRegistrationController::class, 'finalize'])->name('project.register.finalize');

// Admin Reports
Route::get('/admin/projects', [ProjectReportController::class, 'index'])->name('admin.projects.index');
Route::get('/admin/projects/export/excel', [ProjectReportController::class, 'exportExcel'])->name('admin.projects.export.excel');
Route::get('/admin/projects/export/pdf', [ProjectReportController::class, 'exportPdf'])->name('admin.projects.export.pdf');