<?php

use App\Http\Controllers\Auth\AccountSecurityController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', function () {
        return view('auth.recovery');
    })->name('password.request');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

Route::middleware('guest')->group(function () {
    Route::get('/two-factor-challenge', [AuthenticatedSessionController::class, 'challenge'])->name('security.challenge');
    Route::post('/two-factor-challenge', [AuthenticatedSessionController::class, 'verify'])->name('security.verify');
});
Route::middleware('auth')->prefix('account/security')->group(function () {
    $controller = AccountSecurityController::class;
    Route::get('/', [$controller, 'index'])->name('security.index');
    Route::get('/confirm', [$controller, 'confirmation'])->name('security.confirm');
    Route::post('/confirm', [$controller, 'confirm'])->name('security.confirm.store');
    Route::post('/password', [$controller, 'password'])->name('security.password');
    Route::post('/sessions', [$controller, 'sessions'])->name('security.sessions');
    Route::post('/two-factor/{action}', [$controller, 'twoFactor'])->name('security.two-factor');
    Route::post('/reset/{user}', [$controller, 'reset'])->name('security.reset');
});
