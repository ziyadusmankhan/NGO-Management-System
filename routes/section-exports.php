<?php

use App\Domain\Organization\Services\SectionRegister;
use App\Http\Controllers\Organization\SectionExportController;
use Illuminate\Support\Facades\Route;

Route::get('/exports/sections/{kind}', SectionExportController::class)->whereIn('kind', array_keys(SectionRegister::TYPES))->middleware(['auth', 'throttle:20,1'])->name('sections.export');
