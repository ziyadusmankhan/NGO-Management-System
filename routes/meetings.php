<?php

use App\Http\Controllers\Operations\MeetingController;
use Illuminate\Support\Facades\Route;

Route::prefix('operations/meetings')->name('operations.meetings.')->middleware(['auth', 'permission:meetings.view'])->group(function () {
    Route::get('/', [MeetingController::class, 'index'])->name('index');
    Route::middleware('permission:meetings.manage')->group(function () {
        Route::get('/create', [MeetingController::class, 'create'])->name('create');
        Route::post('/', [MeetingController::class, 'store'])->name('store');
        Route::get('/{meeting}/edit', [MeetingController::class, 'edit'])->name('edit');
        Route::put('/{meeting}', [MeetingController::class, 'update'])->name('update');
        Route::post('/{meeting}/minutes', [MeetingController::class, 'minutes'])->name('minutes');
        Route::post('/{meeting}/cancel', [MeetingController::class, 'cancel'])->name('cancel');
        Route::delete('/{meeting}', [MeetingController::class, 'archive'])->name('archive');
        Route::post('/{meeting}/restore', [MeetingController::class, 'restore'])->whereNumber('meeting')->name('restore');
    });
    Route::get('/{meeting}', [MeetingController::class, 'show'])->name('show');
});
