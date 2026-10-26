<?php

use App\Domain\Procurement\Models\Procurement;
use App\Domain\Procurement\Services\ProcurementPaymentService;
use App\Domain\Procurement\Services\ProcurementWorkflowService;
use App\Domain\Users\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
[$script,$database,$id,$actorId,$kind,$key,$amount,$ready,$release] = $argv;
$dir = realpath(dirname($database));
if (! $dir || dirname($dir) !== realpath(sys_get_temp_dir()) || ! str_starts_with(basename($dir), 'procurement-phase9-')) {
    throw new RuntimeException('Isolated project test database required.');
}
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database, 'database.connections.sqlite.busy_timeout' => 10000,
    'database.connections.sqlite.journal_mode' => 'WAL', 'database.connections.sqlite.synchronous' => 'OFF', 'filesystems.disks.private.root' => $dir.'/evidence', 'cache.default' => 'array', 'session.driver' => 'array']);
DB::purge('sqlite');
Storage::forgetDisk('private');
$application = Procurement::findOrFail($id);
$service = app(ProcurementPaymentService::class);
$evidence = fn () => new UploadedFile($dir.'/receipt.pdf', 'receipt.pdf', 'application/pdf', null, true);
$data = ['version' => $application->version, 'amount' => $amount, 'idempotency_key' => $key, 'payment_date' => now('Asia/Karachi')->toDateString(), 'payment_method' => 'cash', 'receipt' => 'RACE-1', 'evidence' => $evidence()];
if ($kind === 'pay') {
    $data['invoice_line_id'] = $application->lines()->first()->id;
}
if ($kind === 'refund') {
    $data['original_payment_id'] = $application->movements()->where('kind', 'payment')->first()->id;
    $data['return_id'] = $application->returns()->first()->id;
}
if ($kind === 'amend') {
    $data = ['version' => $application->version, 'reason' => 'Amend budget'];
}
if ($kind === 'approve') {
    $data = ['version' => $application->version, 'decision' => 'accept', 'notes' => 'Concurrent committee approval', 'evidence' => $evidence()] + json_decode(file_get_contents($dir.'/resolution-'.$id.'.json'), true, 512, JSON_THROW_ON_ERROR);
}
touch($ready);
$deadline = microtime(true) + 30;
while (! file_exists($release)) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Start timeout');
    }usleep(10000);
    clearstatcache(true, $release);
}
try {
    if ($kind === 'approve') {
        app(ProcurementWorkflowService::class)->decide($application, 'committee', $data, User::findOrFail($actorId));
    } elseif ($kind === 'amend') {
        app(ProcurementWorkflowService::class)->transition($application, 'amend', $data, User::findOrFail($actorId));
    } else {
        $service->record($application, $kind, $data, User::findOrFail($actorId));
    }
    echo 'ACCEPTED';
} catch (ValidationException $e) {
    fwrite(STDERR, json_encode($e->errors()));
    echo 'BLOCKED';
}
