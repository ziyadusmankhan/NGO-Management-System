<?php

// Read-only inventory: route ownership and Blade dependencies, including print/secondary views.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$result = ['routes' => [], 'templates' => []];
foreach (Illuminate\Support\Facades\Route::getRoutes() as $route) {
    if (! in_array('GET', $route->methods())) { continue; }
    $views = [];
    $action = $route->getActionName();
    if (str_contains($action, '@')) {
        [$class, $method] = explode('@', $action);
        if (class_exists($class) && method_exists($class, $method)) {
            $reflection = new ReflectionMethod($class, $method);
            $lines = file($reflection->getFileName());
            $source = implode('', array_slice($lines, $reflection->getStartLine()-1, $reflection->getEndLine()-$reflection->getStartLine()+1));
            preg_match_all('/view\(\s*[\'\"]([^\'\"]+)[\'\"]/', $source, $matches);
            $views = $matches[1];
        }
    }
    $result['routes'][] = ['uri' => $route->uri(), 'name' => $route->getName(), 'action' => $action, 'views' => $views, 'middleware' => $route->gatherMiddleware()];
}
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS)) as $file) {
    if (! str_ends_with($file, '.blade.php')) { continue; }
    $source = file_get_contents($file);
    preg_match_all('/@(extends|include)\(\s*[\'\"]([^\'\"]+)[\'\"]/', $source, $matches, PREG_SET_ORDER);
    $result['templates'][] = ['path' => str_replace('\\', '/', substr($file, strlen(base_path())+1)), 'dependencies' => array_column($matches, 2), 'forms' => substr_count($source, '<form'), 'tables' => substr_count($source, '<table'), 'standalone' => str_contains(strtolower($source), '<html'), 'verification' => 'pending visual review'];
}
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
