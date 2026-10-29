<?php

// Mechanical Blade migration: use existing route permissions for static links.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$permissions = [];
foreach (Illuminate\Support\Facades\Route::getRoutes() as $route) {
    foreach ($route->gatherMiddleware() as $middleware) {
        if (str_starts_with($middleware, 'permission:')) {
            $permissions[$route->getName()][] = substr($middleware, 11);
        }
    }
}
if (in_array('--organization-only', $argv, true)) {
    $permissions = [
        'members.index' => ['members.view'], 'members.show' => ['members.view'],
        'members.create' => ['members.create'], 'members.edit' => ['members.update'],
        'committees.index' => ['committees.view'], 'committees.show' => ['committees.view'],
        'committees.create' => ['committees.create'], 'committees.edit' => ['committees.update'],
        'users.roles' => ['roles.manage'],
    ];
}
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS)) as $file) {
    if (! str_ends_with($file, '.blade.php')) { continue; }
    $source = file_get_contents($file);
    $source = preg_replace_callback('/<a\b[^>]*>.*?<\/a>/s', function ($match) use ($permissions) {
        if (! preg_match('/route\(\s*[\'\"]([\w.-]+)[\'\"]/', $match[0], $route)) { return $match[0]; }
        $required = $permissions[$route[1]] ?? [];
        if (! $required) { return $match[0]; }
        $condition = implode(' && ', array_map(fn($permission) => "auth()->user()?->hasPermission('".$permission."')", array_unique($required)));
        return "\n@if(".$condition.')'.$match[0]."@endif\n";
    }, $source);
    file_put_contents($file, $source);
}
echo "Static links now reflect their existing route permissions.\n";
