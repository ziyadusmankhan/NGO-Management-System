<?php

use App\Domain\Organization\Services\FinanceWelfareQuery;
use App\Http\Controllers\Organization\FinanceWelfareExportController;
use Illuminate\Support\Facades\Route;

foreach (FinanceWelfareQuery::DEFINITIONS as $kind => [$url,$name,$permissions]) {
    Route::get($url, FinanceWelfareExportController::class)->defaults('kind', $kind)->name($name)
        ->middleware(['auth', 'throttle:20,1', ...array_map(fn ($permission) => 'permission:'.$permission, $permissions)]);
}
