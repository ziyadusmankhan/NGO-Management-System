<?php
namespace Tests\Feature;

use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Qard\Models\QardMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesQardFixtures;
use Tests\TestCase;

class QardConcurrencyTest extends TestCase
{
    use CreatesQardFixtures;

    #[DataProvider('cases')]
    public function test_financial_races(string $case):void
    {
        $directory=sys_get_temp_dir().DIRECTORY_SEPARATOR.'qard-phase6-'.Str::uuid();
        File::makeDirectory($directory);
        $database=$directory.DIRECTORY_SEPARATOR.'race.sqlite';touch($database);$workers=[];
        try{
            config(['database.connections.sqlite.database'=>$database,'database.connections.sqlite.busy_timeout'=>10000,
            'database.connections.sqlite.journal_mode'=>'WAL','database.connections.sqlite.synchronous'=>'OFF',
            'filesystems.disks.private.root'=>$directory.'/evidence']);
            DB::purge('sqlite');Storage::forgetDisk('private');
            $this->assertSame(0,Artisan::call('migrate',['--force'=>true]));
            $actor=$this->qardActor();
            $loan=$case==='repayment_cap'?$this->activeLoan($actor):$this->signedLoan($actor);
            $kind=$case==='repayment_cap'?'repayment':'disbursement';
            $amount=$case==='repayment_cap'?'400.00':'500.00';
            $key=(string)Str::uuid();
            $jobs=[[$loan->id,$key],[$loan->id,$case==='same_key'?$key:(string)Str::uuid()]];
            if($case==='same_fund'){
                $other=$this->signedLoan($actor);
                // Both approved loans compete for the same cash, with matching signed fund terms.
                $other->update(['fund_id'=>$loan->fund_id]);
                $terms=$other->agreements()->first()->terms;$terms['fund_id']=$loan->fund_id;
                $other->agreements()->first()->update(['terms'=>$terms]);
                $loan->fund->update(['opening_balance'=>'500.00']);
                $jobs[1][0]=$other->id;
            }
            $release=$directory.'/release';
            $php = \Tests\Support\PhpWorker::command();
            foreach($jobs as $i=>[$id,$requestKey]){
                $worker=new Process(array_merge($php,[base_path('tests/Support/qard_worker.php'),$database,(string)$id,(string)$actor->id,$kind,$requestKey,$amount,$directory.'/ready-'.$i,$release]),base_path(),['APP_ENV'=>'testing']);
                $worker->setTimeout(45)->start();$workers[]=$worker;
            }
            $deadline=microtime(true)+30;
            while(!file_exists($directory.'/ready-0')||!file_exists($directory.'/ready-1')){
                if(microtime(true)>$deadline)$this->fail('Worker startup failed: '.implode(' ',array_map(fn($p)=>$p->getErrorOutput().$p->getOutput(),$workers)));
                usleep(10000);clearstatcache();
            }
            touch($release);$results=[];
            foreach($workers as $worker){$this->assertSame(0,$worker->wait(),$worker->getErrorOutput().$worker->getOutput());$results[]=trim($worker->getOutput());}
            sort($results);
            $this->assertSame($case==='same_key'?['ACCEPTED','ACCEPTED']:['ACCEPTED','BLOCKED'],$results,implode(' ',array_map(fn($p)=>$p->getErrorOutput(),$workers)));
            $this->assertSame($case==='repayment_cap'?2:1,Transaction::where('status','posted')->count());
            foreach(QardMovement::all() as $movement)$this->assertSame(1,$movement->transaction()->count());
            $this->assertSame($case==='repayment_cap'?'900.00':($case==='same_fund'?'0.00':'500.00'),app(FundBalanceService::class)->balance($loan->fund->fresh()));
        }finally{
            foreach($workers as $worker)if($worker->isRunning())$worker->stop();
            DB::purge('sqlite');Storage::forgetDisk('private');
            $resolved=realpath($directory);
            if($resolved && dirname($resolved)===realpath(sys_get_temp_dir()) && str_starts_with(basename($resolved),'qard-phase6-'))File::deleteDirectory($resolved);
        }
    }
    public static function cases():array{return [['same_key'],['different_keys'],['same_fund'],['repayment_cap']];}
}
