<?php

use App\Http\Controllers\System\GlobalSearchController;
use App\Http\Controllers\System\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/search', GlobalSearchController::class)->name('search.index');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/change', [NotificationController::class, 'change'])->name('notifications.change');
});
