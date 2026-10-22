<?php

use App\Http\Controllers\Qard\QardController;
use Illuminate\Support\Facades\Route;

Route::prefix('qard')->name('qard.')->middleware(['auth', 'permission:qard.view'])->group(function () {
    Route::get('/', [QardController::class, 'index'])->name('index');
    Route::get('/create', [QardController::class, 'create'])->middleware('permission:qard.manage')->name('create');
    Route::get('/{loan}/edit', [QardController::class, 'edit'])->middleware('permission:qard.manage')->name('edit');
    Route::get('/{loan}/agreement', [QardController::class, 'agreement'])->name('agreement');
    Route::get('/{loan}/documents/{document}', [QardController::class, 'document'])->name('documents.show');
    Route::get('/{loan}/repayment', [QardController::class, 'repayment'])->middleware('permission:qard.repay')->name('repayment');
    Route::get('/{loan}/recovery', [QardController::class, 'repayment'])->middleware('permission:qard.repay')->name('recovery');
    Route::get('/{loan}/recycling', [QardController::class, 'show'])->name('recycling');
    Route::get('/{loan}', [QardController::class, 'show'])->name('show');

    Route::middleware('throttle:60,1')->group(function () {
        Route::post('/', [QardController::class, 'store'])->middleware('permission:qard.manage')->name('store');
        Route::put('/{loan}', [QardController::class, 'update'])->middleware('permission:qard.manage')->name('update');
        foreach (['submit' => 'manage', 'verify' => 'verify', 'sign' => 'agreements', 'revise' => 'manage',
            'disburse' => 'disburse', 'repay' => 'repay', 'followup' => 'followups'] as $action => $permission) {
            Route::post('/{loan}/'.$action, [QardController::class, $action])->middleware('permission:qard.'.$permission)->name($action);
        }
        Route::post('/{loan}/decisions/{stage}', [QardController::class, 'decide'])
            ->whereIn('stage', ['implementation', 'secretary', 'committee'])->name('decide');
        Route::post('/{loan}/movements/{movement}/void', [QardController::class, 'void'])->middleware('permission:qard.void')->name('void');
    });
});
