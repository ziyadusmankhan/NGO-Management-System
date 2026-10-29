<?php

use App\Http\Controllers\Records\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('records/reports')->name('records.reports.')->middleware(['auth', 'permission:reports.view'])->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::post('/', [ReportController::class, 'store'])->middleware('permission:reports.generate')->name('store');
    Route::get('/{report}', [ReportController::class, 'show'])->name('show');
    Route::get('/{report}/export', [ReportController::class, 'export'])->middleware('permission:reports.generate')->name('export');
});
