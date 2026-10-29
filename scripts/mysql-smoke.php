<?php

use App\Domain\Users\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ViewErrorBag;

// Render read-only module entry screens against an imported rehearsal schema.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = $argv[1] ?? '';
if (! app()->environment('local') || ! preg_match('/^foundation_management(?:_rehearsal_[0-9]+)?$/', $database)) {
    throw new RuntimeException('Specify the transfer/rehearsal schema.');
}
config(['database.default' => 'mysql', 'database.connections.mysql' => array_replace(config('database.connections.mysql'), ['url' => null, 'host' => env('MYSQL_MIGRATION_HOST'), 'port' => env('MYSQL_MIGRATION_PORT', 3306), 'database' => $database, 'username' => env('MYSQL_MIGRATION_USERNAME'), 'password' => env('MYSQL_MIGRATION_PASSWORD')]), 'session.driver' => 'array', 'cache.default' => 'array']);
DB::purge('mysql');
$user = User::whereHas('roles', fn ($q) => $q->where('slug', 'administrator'))->where('status', 'active')->firstOrFail();
Auth::guard()->setUser($user);
app('session')->driver()->start();
app('view')->share('errors', new ViewErrorBag);
$success = [];
$failed = [];
foreach (Route::getRoutes() as $route) {
    $name = $route->getName();
    if (! $name || ! in_array('GET', $route->methods()) || str_contains($route->uri(), '{') || ! preg_match('/\.index$|^dashboard$/', $name) || ! str_contains($route->getActionName(), '@')) {
        continue;
    }
    try {
        $request = Request::create('/'.$route->uri(), 'GET');
        $request->setLaravelSession(app('session')->driver());
        $route->bind($request);
        $request->setUserResolver(fn () => $user);
        $request->setRouteResolver(fn () => $route);
        $app->instance('request', $request);
        [$class,$method] = explode('@', $route->getActionName());
        $response = $app->call([$app->make($class), $method]);
        if ($response instanceof View) {
            $response->render();
        } elseif (method_exists($response, 'getContent')) {
            $response->getContent();
        }
        $success[] = $name;
    } catch (Throwable $e) {
        $issue = 'unclassified';
        foreach (['Session store not set', 'Undefined variable $errors', 'request does not have a session'] as $known) {
            if (str_contains($e->getMessage(), $known)) {
                $issue = $known;
            }
        }
        $failed[] = ['route' => $name, 'type' => get_class($e), 'code' => $e->getCode(), 'issue' => $issue];
    }
}
echo json_encode(['database' => $database, 'rendered' => count($success), 'routes' => $success, 'failed' => $failed], JSON_PRETTY_PRINT),PHP_EOL;
exit($failed ? 1 : 0);
