<?php

use App\Http\Controllers\Records\AuditController;
use Illuminate\Support\Facades\Route;

Route::prefix('records/audit')->name('records.audit.')->middleware(['auth', 'permission:audit.view'])->group(function () {
    Route::get('/', [AuditController::class, 'index'])->name('index');
    Route::get('/export', [AuditController::class, 'export'])->middleware('permission:audit.export')->name('export');
    Route::get('/{event}', [AuditController::class, 'show'])->whereNumber('event')->name('show');
});
