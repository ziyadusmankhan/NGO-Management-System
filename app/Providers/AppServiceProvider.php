<?php

namespace App\Providers;

use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            return $user->hasPermission($ability) ? true : null;
        });

        $this->loadMigrationsFrom(
            glob(database_path('migrations/*'), GLOB_ONLYDIR)
        );
    }
}