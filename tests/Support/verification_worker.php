<?php

use App\Domain\Users\Models\User;
use App\Http\Controllers\Welfare\BeneficiaryVerificationController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $evidenceRoot, $applicationId, $actorId, $readyFile, $releaseFile] = $argv;
$directory = realpath(dirname($database));
if (! $directory || dirname($directory) !== realpath(sys_get_temp_dir())
    || ! str_starts_with(basename($directory), 'welfare-phase1-')) {
    throw new RuntimeException('Worker only accepts an isolated Phase 1 temporary database.');
}
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config([
    'database.default' => 'sqlite', 'database.connections.sqlite.database' => $database,
    'database.connections.sqlite.busy_timeout' => 10000, 'database.connections.sqlite.journal_mode' => 'WAL', 'database.connections.sqlite.synchronous' => 'OFF',
    'filesystems.disks.private.root' => $evidenceRoot,
    'cache.default' => 'array', 'session.driver' => 'array',
]);
DB::purge('sqlite');
$actor = User::findOrFail($actorId);
touch($readyFile);
$deadline = microtime(true) + 30;
while (! file_exists($releaseFile)) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Timed out waiting for concurrent test start.');
    }
    usleep(10000);
    clearstatcache(true, $releaseFile);
}
try {
    Auth::setUser($actor);
    app('session')->driver()->start();
    $request = Request::create('/welfare/verification', 'POST', ['application_id' => $applicationId, 'assigned_to' => $actorId, 'visit_date' => now()->toDateString(), 'priority' => 'normal']);
    $request->setLaravelSession(app('session')->driver());
    $request->setUserResolver(fn () => $actor);
    app()->instance('request', $request);
    $response = app(BeneficiaryVerificationController::class)->store($request);
    if (app('session')->get('errors')) {
        echo 'DUPLICATE';
        exit;
    }
    echo 'CREATED';
} catch (ValidationException $exception) {
    if (! isset($exception->errors()['application_id'])) {
        throw $exception;
    }
    echo 'DUPLICATE';
}
