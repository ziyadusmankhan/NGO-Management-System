<?php

namespace Tests\Feature;

use App\Domain\Business\Models\BusinessMovement;
use App\Domain\Business\Services\BusinessPaymentService;
use App\Domain\Business\Services\BusinessWorkflowService;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Transactions\Models\Transaction;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\Support\CreatesBusinessFixtures;
use Tests\Support\ValidUpload;
use Tests\TestCase;

class BusinessConcurrencyTest extends TestCase
{
    use CreatesBusinessFixtures;

    #[DataProvider('cases')]
    public function test_simultaneous_business_actions(string $case): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'business-phase7-'.Str::uuid();
        File::makeDirectory($directory);
        $database = $directory.'/race.sqlite';
        touch($database);
        $workers = [];
        try {
            config(['database.connections.sqlite.database' => $database, 'database.connections.sqlite.busy_timeout' => 10000, 'database.connections.sqlite.journal_mode' => 'WAL',
                'database.connections.sqlite.synchronous' => 'OFF', 'filesystems.disks.private.root' => $directory.'/evidence']);
            DB::purge('sqlite');
            Storage::forgetDisk('private');
            $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
            $receiptFile = ValidUpload::pdf();
            file_put_contents($directory.'/receipt.pdf', file_get_contents($receiptFile->getRealPath()));
            $officer = $this->businessActor();
            $payer = $this->businessActor();
            $p = app(BusinessPaymentService::class);
            $a = $case === 'repayment_cap' ? $this->fundedBusiness($officer) : $this->signedBusiness($officer);
            $kind = $case === 'repayment_cap' ? 'repayment' : 'disbursement';
            $amount = $case === 'repayment_cap' ? '400.00' : '500.00';
            $key = (string) Str::uuid();
            $jobs = [[$a->id, $kind, $key, $amount], [$a->id, $kind, $case === 'same_key' ? $key : (string) Str::uuid(), $amount]];
            if ($case === 'same_fund') {
                $other = $this->signedBusiness($officer);
                $other->update(['fund_id' => $a->fund_id]);
                $other->agreements()->first()->update(['terms' => app(BusinessWorkflowService::class)->terms($other)]);
                $a->fund->update(['opening_balance' => '500.00']);
                $jobs[1][0] = $other->id;
            }
            if ($case === 'finalize_race') {
                $p->record($a, 'disbursement', $this->businessPayment($a, 'disbursement', '100.00'), $payer);
                $jobs[0][1] = 'finalize';
                $jobs[1][3] = '100.00';
            }
            $release = $directory.'/release';
            $php = [PHP_BINARY, '-n'];
            foreach (['mbstring', 'pdo_sqlite', 'sqlite3', 'fileinfo', 'openssl'] as $extension) {
                $path = ini_get('extension_dir').DIRECTORY_SEPARATOR.(PHP_OS_FAMILY === 'Windows' ? 'php_' : '').$extension.'.'.PHP_SHLIB_SUFFIX;
                if (! is_file($path)) {
                    $path = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.$path;
                }
                if (is_file($path)) {
                    array_push($php, '-d', 'extension='.$path);
                }
            }
            foreach ($jobs as $i => [$id,$jobKind,$requestKey,$jobAmount]) {
                $worker = new Process(array_merge($php, [base_path('tests/Support/business_worker.php'), $database, (string) $id, (string) $payer->id, $jobKind, $requestKey, $jobAmount, $directory.'/ready-'.$i, $release]), base_path(), ['APP_ENV' => 'testing']);
                $worker->setTimeout(45)->start();
                $workers[] = $worker;
            }
            $deadline = microtime(true) + 30;
            while (! file_exists($directory.'/ready-0') || ! file_exists($directory.'/ready-1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Worker startup failed: '.implode(' ', array_map(fn ($p) => $p->getErrorOutput().$p->getOutput(), $workers)));
                }
                usleep(10000);
                clearstatcache();
            }
            touch($release);
            $results = [];
            foreach ($workers as $worker) {
                $this->assertSame(0, $worker->wait(), $worker->getErrorOutput().$worker->getOutput());
                $results[] = trim($worker->getOutput());
            }
            sort($results);
            $this->assertSame($case === 'same_key' ? ['ACCEPTED', 'ACCEPTED'] : ['ACCEPTED', 'BLOCKED'], $results, implode(' ', array_map(fn ($p) => $p->getErrorOutput(), $workers)));
            foreach (BusinessMovement::all() as $movement) {
                $this->assertSame(1, $movement->transaction()->count());
            }
            if ($case === 'finalize_race') {
                $a = $a->fresh();
                $this->assertSame($a->funding_status === 'finalized' ? 10000 : 20000, $a->releasedMinor());
                $this->assertSame($a->funding_status === 'finalized' ? 3 : 0, $a->installments()->count());
            } else {
                $this->assertSame($case === 'repayment_cap' ? 2 : 1, Transaction::where('status', 'posted')->count());
                $this->assertSame($case === 'repayment_cap' ? '900.00' : ($case === 'same_fund' ? '0.00' : '500.00'), app(FundBalanceService::class)->balance($a->fund->fresh()));
            }
        } finally {
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop();
                }
            }
            DB::purge('sqlite');
            Storage::forgetDisk('private');
            $resolved = realpath($directory);
            if ($resolved && dirname($resolved) === realpath(sys_get_temp_dir()) && str_starts_with(basename($resolved), 'business-phase7-')) {
                File::deleteDirectory($resolved);
            }
        }
    }

    public static function cases(): array
    {
        return [['same_key'], ['different_keys'], ['same_fund'], ['repayment_cap'], ['finalize_race']];
    }
}
