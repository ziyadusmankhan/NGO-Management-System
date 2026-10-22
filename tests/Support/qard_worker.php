<?php
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Domain\Users\Models\User;
use App\Domain\Qard\Models\QardLoan;
use App\Domain\Qard\Services\QardPaymentService;

require dirname(__DIR__,2).'/vendor/autoload.php';
[$script,$database,$loanId,$actorId,$kind,$key,$amount,$ready,$release]=$argv;
$dir=realpath(dirname($database));
if (!$dir || dirname($dir)!==realpath(sys_get_temp_dir()) || !str_starts_with(basename($dir),'qard-phase6-')) throw new RuntimeException('Isolated Qard test database required.');
$app=require dirname(__DIR__,2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default'=>'sqlite','database.connections.sqlite.database'=>$database,
'database.connections.sqlite.busy_timeout'=>10000,'database.connections.sqlite.journal_mode'=>'WAL','database.connections.sqlite.synchronous'=>'OFF',
'filesystems.disks.private.root'=>$dir.'/evidence','cache.default'=>'array','session.driver'=>'array']);
DB::purge('sqlite'); Storage::forgetDisk('private');
touch($ready);$deadline=microtime(true)+30;
while(!file_exists($release)){if(microtime(true)>$deadline)throw new RuntimeException('Start timeout');usleep(10000);clearstatcache(true,$release);}
try {
    app(QardPaymentService::class)->record(QardLoan::findOrFail($loanId),$kind,[
        'amount'=>$amount,'idempotency_key'=>$key,'payment_date'=>now('Asia/Karachi')->toDateString(),'payment_method'=>'cash',
    ],User::findOrFail($actorId));
    echo 'ACCEPTED';
} catch(ValidationException $e){fwrite(STDERR,json_encode($e->errors()));echo 'BLOCKED';}
