<?php

use App\Http\Controllers\Procurement\ProcurementController;
use Illuminate\Support\Facades\Route;

Route::prefix('operations/procurement')->name('operations.procurement.')->middleware(['auth', 'permission:procurement.view'])->group(function () {
    Route::get('/', [ProcurementController::class, 'index'])->name('index');
    Route::get('/create', [ProcurementController::class, 'create'])->name('create');
    Route::post('/', [ProcurementController::class, 'store'])->name('store');
    Route::get('/settings', [ProcurementController::class, 'settings'])->name('settings');
    Route::post('/suppliers/{supplier?}', [ProcurementController::class, 'supplier'])->name('suppliers.save');
    Route::post('/policy', [ProcurementController::class, 'policy'])->name('policy');
    Route::get('/reports', [ProcurementController::class, 'reports'])->name('reports');
    Route::get('/{procurement}/edit', [ProcurementController::class, 'edit'])->name('edit');
    Route::put('/{procurement}', [ProcurementController::class, 'update'])->name('update');
    Route::get('/{procurement}/order', [ProcurementController::class, 'order'])->withTrashed()->name('order');
    Route::get('/{procurement}/documents/{document}', [ProcurementController::class, 'document'])->withTrashed()->name('documents');
    Route::get('/{procurement}', [ProcurementController::class, 'show'])->withTrashed()->name('show');
    Route::middleware('throttle:60,1')->group(function () {
        foreach (['quote', 'submit', 'issue', 'amend', 'withdraw', 'resume', 'receive', 'invoice', 'pay', 'return', 'refund', 'void', 'close', 'cancel', 'archive'] as $action) {
            Route::post('/{procurement}/'.$action, [ProcurementController::class, 'action'])->defaults('action', $action)->withTrashed()->name($action);
        }
        Route::post('/{procurement}/decisions/{stage}', [ProcurementController::class, 'decide'])->whereIn('stage', ['coordinator', 'secretary', 'committee'])->name('decide');
    });
});
