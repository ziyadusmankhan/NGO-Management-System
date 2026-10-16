<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Migrations are organized into subfolders by domain
        // (database/migrations/users, /finance, /welfare, etc.)
        // rather than Laravel's default flat structure, so each
        // subfolder must be registered explicitly here.
        foreach (glob(database_path('migrations/*'), GLOB_ONLYDIR) as $path) {
            $this->loadMigrationsFrom($path);
        }
    }
}
