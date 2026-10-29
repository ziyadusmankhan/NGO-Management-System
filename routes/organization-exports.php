<?php

use App\Http\Controllers\Organization\DirectoryExportController;
use Illuminate\Support\Facades\Route;

foreach (['members' => ['members/export', 'members.export', 'members.view'], 'committees' => ['committees/export', 'committees.export', 'committees.view'],
    'users' => ['users/export', 'users.export', 'users.view'], 'roles' => ['users/roles/export', 'users.roles.export', 'roles.manage']] as $kind => [$url, $name, $permission]) {
    Route::get($url, DirectoryExportController::class)->defaults('kind', $kind)
        ->middleware(['auth', 'permission:'.$permission, 'throttle:20,1'])->name($name);
}
