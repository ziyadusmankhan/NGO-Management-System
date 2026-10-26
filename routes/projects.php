<?php

use App\Http\Controllers\Projects\ProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('projects')->name('projects.')->middleware(['auth', 'permission:projects.view'])->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('index');
    Route::get('/categories', [ProjectController::class, 'categories'])->middleware('permission:projects.categories')->name('categories');
    Route::post('/categories', [ProjectController::class, 'saveCategory'])->middleware('permission:projects.categories')->name('categories.store');
    Route::put('/categories/{category}', [ProjectController::class, 'saveCategory'])->middleware('permission:projects.categories')->name('categories.update');
    Route::get('/reports', [ProjectController::class, 'reports'])->middleware('permission:projects.reports')->name('reports');
    Route::get('/create', [ProjectController::class, 'create'])->middleware('permission:projects.manage')->name('create');
    Route::post('/', [ProjectController::class, 'store'])->middleware('permission:projects.manage')->name('store');
    Route::get('/{project}/edit', [ProjectController::class, 'edit'])->name('edit');
    Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
    Route::get('/{project}/documents/{document}', [ProjectController::class, 'document'])->withTrashed()->name('documents.show');
    Route::get('/{project}', [ProjectController::class, 'show'])->withTrashed()->name('show');
    Route::middleware('throttle:60,1')->group(function () {
        foreach (['submit', 'verify', 'amend', 'reopen', 'withdraw', 'resume', 'close', 'cancel', 'archive', 'start', 'monitor', 'complete', 'pay', 'refund', 'dedicated-fund'] as $action) {
            $route = Route::post('/{project}/'.$action, [ProjectController::class, 'action'])->defaults('action', $action)->name($action);
            if ($action === 'refund') {
                $route->withTrashed();
            }
        }
        Route::post('/{project}/decisions/{stage}', [ProjectController::class, 'decide'])->whereIn('stage', ['coordinator', 'secretary', 'committee'])->name('decide');
        Route::post('/{project}/movements/{movement}/void', [ProjectController::class, 'void'])->withTrashed()->name('void');
    });
});
