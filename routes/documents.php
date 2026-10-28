<?php

use App\Http\Controllers\Records\DocumentController;
use Illuminate\Support\Facades\Route;

Route::prefix('records/documents')->name('records.documents.')->middleware(['auth', 'permission:documents.view'])->group(function () {
    Route::get('/', [DocumentController::class, 'index'])->name('index');
    Route::get('/create', [DocumentController::class, 'create'])->middleware('permission:documents.upload')->name('create');
    Route::post('/', [DocumentController::class, 'store'])->middleware(['permission:documents.upload', 'throttle:20,1'])->name('store');
    Route::get('/{document}', [DocumentController::class, 'show'])->whereNumber('document')->name('show');
    Route::get('/{document}/versions/{version}', [DocumentController::class, 'download'])->name('download');
    Route::post('/{document}/versions', [DocumentController::class, 'version'])->middleware('throttle:20,1')->name('versions');
    Route::post('/{document}/{action}', [DocumentController::class, 'change'])->whereIn('action', ['manage', 'share', 'archive', 'restore'])->name('change');
});
