<?php

use App\Http\Controllers\Operations\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('operations/tasks')->name('operations.tasks.')->middleware(['auth', 'permission:tasks.view'])->group(function () {
    Route::get('/', [TaskController::class, 'index'])->name('index');
    Route::middleware('permission:tasks.manage')->group(function () {
        Route::get('/create', [TaskController::class, 'create'])->name('create');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::get('/{task}/edit', [TaskController::class, 'edit'])->name('edit');
        Route::put('/{task}', [TaskController::class, 'update'])->name('update');
        Route::post('/{task}/status', [TaskController::class, 'status'])->name('status');
        Route::delete('/{task}', [TaskController::class, 'archive'])->name('archive');
        Route::post('/{task}/restore', [TaskController::class, 'restore'])->whereNumber('task')->name('restore');
    });
    Route::post('/{task}/comments', [TaskController::class, 'comment'])->name('comments');
    Route::get('/{task}', [TaskController::class, 'show'])->name('show');
});
