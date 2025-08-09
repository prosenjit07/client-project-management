<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProjectRegistrationController;
use App\Http\Controllers\Admin\ProjectReportController;

Route::get('/', fn() => Inertia::render('Index'))->name('home');

Route::controller(ProjectRegistrationController::class)->group(function () {
    Route::get('/projects/register', 'create')->name('project.register.create');
    Route::post('/projects/register/finalize', 'finalize')->name('project.register.finalize');
});


Route::prefix('admin/projects')->name('admin.projects.')->group(function () {
    Route::resource('/', ProjectReportController::class)->only(['index'])->names([
        'index' => 'index'
    ]);
    Route::get('/export/excel', [ProjectReportController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/pdf', [ProjectReportController::class, 'exportPdf'])->name('export.pdf');
});
