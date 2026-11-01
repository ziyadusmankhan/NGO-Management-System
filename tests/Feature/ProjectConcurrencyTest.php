<?php

namespace Tests\Feature;

use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Projects\Models\ProjectMovement;
use App\Domain\Projects\Services\ProjectPaymentService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\Support\CreatesProjectFixtures;
use Tests\Support\ValidUpload;
use Tests\TestCase;

class ProjectConcurrencyTest extends TestCase
{
    use CreatesProjectFixtures;

    #[DataProvider('cases')]
    public function test_simultaneous_project_actions(string $case): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'projects-phase8-'.Str::uuid();
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
            $officer = $this->projectActor();
            $payer = $this->projectActor();
            $p = app(ProjectPaymentService::class);
            $a = $this->approveProject($this->draftProject($officer), $officer);
            $kind = $case === 'refund_cap' ? 'refund' : 'payment';
            if ($case === 'refund_cap') {
                $p->record($a, 'payment', $this->projectPayment($a), $payer);
            }
            $amount = $case === 'refund_cap' ? '400.00' : '500.00';
            $key = (string) Str::uuid();
            $jobs = [[$a->id, $kind, $key, $amount], [$a->id, $kind, $case === 'same_key' ? $key : (string) Str::uuid(), $amount]];
            if ($case === 'same_fund') {
                $other = $this->approveProject($this->draftProject($officer), $officer);
                $other->update(['fund_id' => $a->fund_id]);
                $a->fund->update(['opening_balance' => '500.00']);
                $jobs[1][0] = $other->id;
            }
            if ($case === 'amend_race') {
                $jobs[0][1] = 'amend';
            }
            $release = $directory.'/release';
            $php = \Tests\Support\PhpWorker::command();
            foreach ($jobs as $i => [$id,$jobKind,$requestKey,$jobAmount]) {
                $worker = new Process(array_merge($php, [base_path('tests/Support/project_worker.php'), $database, (string) $id, (string) $payer->id, $jobKind, $requestKey, $jobAmount, $directory.'/ready-'.$i, $release]), base_path(), ['APP_ENV' => 'testing']);
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
            foreach (ProjectMovement::all() as $movement) {
                $this->assertSame(1, $movement->transaction()->count());
            }
            if ($case === 'amend_race') {
                $a = $a->fresh();
                $this->assertSame($a->payments_frozen ? 0 : 50000, $a->grossMinor());
            } else {
                $this->assertSame($case === 'refund_cap' ? 2 : 1, Transaction::where('status', 'posted')->count());
                $this->assertSame($case === 'refund_cap' ? '900.00' : ($case === 'same_fund' ? '0.00' : '500.00'), app(FundBalanceService::class)->balance($a->fund->fresh()));
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
            if ($resolved && dirname($resolved) === realpath(sys_get_temp_dir()) && str_starts_with(basename($resolved), 'projects-phase8-')) {
                File::deleteDirectory($resolved);
            }
        }
    }

    public static function cases(): array
    {
        return [['same_key'], ['different_keys'], ['same_fund'], ['refund_cap'], ['amend_race']];
    }
}
