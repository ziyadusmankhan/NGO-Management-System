<?php

use App\Http\Controllers\Business\BusinessController;
use Illuminate\Support\Facades\Route;

Route::prefix('business')->name('business.')->middleware(['auth', 'permission:business.view'])->group(function () {
    Route::get('/categories', [BusinessController::class, 'categories'])->middleware('permission:business.categories')->name('categories');
    Route::post('/categories', [BusinessController::class, 'saveCategory'])->middleware('permission:business.categories')->name('categories.store');
    Route::put('/categories/{category}', [BusinessController::class, 'saveCategory'])->middleware('permission:business.categories')->name('categories.update');
    Route::get('/reports', [BusinessController::class, 'report'])->middleware('permission:business.reports')->name('reports');
    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/', [BusinessController::class, 'index'])->name('index');
        Route::get('/create', [BusinessController::class, 'create'])->middleware('permission:business.manage')->name('create');
        Route::post('/', [BusinessController::class, 'store'])->middleware('permission:business.manage')->name('store');
        Route::get('/{application}/edit', [BusinessController::class, 'edit'])->middleware('permission:business.manage')->name('edit');
        Route::put('/{application}', [BusinessController::class, 'update'])->middleware('permission:business.manage')->name('update');
        Route::get('/{application}/agreement', [BusinessController::class, 'agreement'])->withTrashed()->name('agreement');
        Route::get('/{application}/documents/{document}', [BusinessController::class, 'document'])->withTrashed()->name('documents.show');
        foreach (['viability', 'recommendation', 'approval', 'disbursement', 'monitoring'] as $page) {
            Route::get('/{application}/'.$page, [BusinessController::class, 'show'])->name($page);
        }
        Route::get('/{application}', [BusinessController::class, 'show'])->withTrashed()->name('show');
        Route::middleware('throttle:60,1')->group(function () {
            foreach (['submit' => 'manage', 'verify' => 'verify', 'sign' => 'agreements', 'revise' => 'manage', 'cancel' => 'close', 'close' => 'close', 'archive' => 'manage',
                'disburse' => 'disburse', 'repay' => 'repay', 'finalize' => 'finalize', 'handover' => 'monitor', 'start' => 'monitor', 'monitor' => 'monitor'] as $action => $permission) {
                Route::post('/{application}/'.$action, [BusinessController::class, 'action'])->defaults('action', $action)->middleware('permission:business.'.$permission)->name($action);
            }
            Route::post('/{application}/decisions/{stage}', [BusinessController::class, 'decide'])->whereIn('stage', ['implementation', 'secretary', 'committee'])->name('decide');
            Route::post('/{application}/movements/{movement}/void', [BusinessController::class, 'void'])->middleware('permission:business.void')->name('void');
        });
    });
});
