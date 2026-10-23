<?php

use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Business\Services\BusinessPaymentService;
use App\Domain\Business\Services\BusinessWorkflowService;
use App\Domain\Users\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
[$script,$database,$id,$actorId,$kind,$key,$amount,$ready,$release] = $argv;
$dir = realpath(dirname($database));
if (! $dir || dirname($dir) !== realpath(sys_get_temp_dir()) || ! str_starts_with(basename($dir), 'business-phase7-')) {
    throw new RuntimeException('Isolated business test database required.');
}
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database, 'database.connections.sqlite.busy_timeout' => 10000,
    'database.connections.sqlite.journal_mode' => 'WAL', 'database.connections.sqlite.synchronous' => 'OFF', 'filesystems.disks.private.root' => $dir.'/evidence', 'cache.default' => 'array', 'session.driver' => 'array']);
DB::purge('sqlite');
Storage::forgetDisk('private');
$application = BusinessApplication::findOrFail($id);
$service = app(BusinessPaymentService::class);
$evidence = fn () => new UploadedFile($dir.'/receipt.pdf', 'receipt.pdf', 'application/pdf', null, true);
$data = ['version' => $application->version, 'amount' => $amount, 'idempotency_key' => $key, 'payment_date' => now('Asia/Karachi')->toDateString(), 'payment_method' => 'cash', 'payee' => 'Borrower', 'receipt' => 'REC-1'];
if ($kind === 'disbursement') {
    $data += ['budget_item_id' => $application->currentBudget()->first()->id, 'evidence' => $evidence()];
}
if ($kind === 'finalize') {
    $data = ['version' => $application->version, 'terms_hash' => app(BusinessWorkflowService::class)->hash($service->finalTerms($application)), 'reason' => 'Funding completed',
        'borrower_name' => 'Borrower', 'foundation_name' => 'Foundation', 'witnesses' => ['Witness A', 'Witness B'], 'signed_on' => now('Asia/Karachi')->toDateString(), 'borrower_file' => $evidence(), 'foundation_file' => $evidence()];
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
    if ($kind === 'finalize') {
        $service->finalize($application, $data, User::findOrFail($actorId));
    } else {
        $service->record($application, $kind, $data, User::findOrFail($actorId));
    }
    echo 'ACCEPTED';
} catch (ValidationException $e) {
    fwrite(STDERR, json_encode($e->errors()));
    echo 'BLOCKED';
}
