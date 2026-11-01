<?php

namespace Tests\Feature;

use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Procurement\Models\ProcurementMovement;
use App\Domain\Procurement\Services\ProcurementGuard;
use App\Domain\Procurement\Services\ProcurementOperationsService;
use App\Domain\Procurement\Services\ProcurementPaymentService;
use App\Domain\Procurement\Services\ProcurementWorkflowService;
use App\Domain\Projects\Services\ProjectWorkflowService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\Support\CreatesProcurementFixtures;
use Tests\Support\ValidUpload;
use Tests\TestCase;

class ProcurementConcurrencyTest extends TestCase
{
    use CreatesProcurementFixtures;

    #[DataProvider('cases')]
    public function test_simultaneous_procurement_actions(string $case): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'procurement-phase9-'.Str::uuid();
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
            $officer = $this->procurementActor();
            $payer = $this->procurementActor();
            $p = app(ProcurementPaymentService::class);
            $a = $this->purchaseApprove($this->purchaseDraft($officer), $officer);
            $line = $this->purchaseInvoice($a, $officer);
            $kind = $case === 'refund_cap' ? 'refund' : 'pay';
            if ($case === 'refund_cap') {
                $p->record($a, 'pay', $this->purchasePayment($a, $line), $payer);
                app(ProcurementOperationsService::class)->returnGoods($a, $this->purchaseVersion($a, ['receipt_id' => $line->receipt_id, 'invoice_line_id' => $line->id, 'quantity' => '2', 'credit_reference' => 'CN-RACE', 'returned_on' => now('Asia/Karachi')->toDateString(), 'refund_due_on' => now('Asia/Karachi')->addDay()->toDateString(), 'reason' => 'Returned goods', 'evidence' => $this->evidence()]), $payer);
            }
            $amount = $case === 'refund_cap' ? '400.00' : '500.00';
            $key = (string) Str::uuid();
            $jobs = [[$a->id, $kind, $key, $amount], [$a->id, $kind, $case === 'same_key' ? $key : (string) Str::uuid(), $amount]];
            if ($case === 'same_fund') {
                $other = $this->purchaseApprove($this->purchaseDraft($officer), $officer);
                $other->update(['fund_id' => $a->fund_id]);
                $this->purchaseInvoice($other, $officer);
                $a->fund->update(['opening_balance' => '500.00']);
                $jobs[1][0] = $other->id;
            }
            if ($case === 'amend_race') {
                $jobs[0][1] = 'amend';
            }
            if ($case === 'competing_orders') {
                $data = $this->projectData($officer);
                $data['items'][0]['kind'] = 'purchase';
                $data['items'][0]['price_checks'] = [['supplier' => 'Worker One', 'amount' => '500.00', 'reference' => 'Q1'], ['supplier' => 'Other', 'amount' => '550.00', 'reference' => 'Q2']];
                $parent = $this->approveProject(app(ProjectWorkflowService::class)->save(null, $data, $officer), $officer);
                $budget = $parent->budgets($parent->approved_revision)->first()->budget_item_id;
                $orders = [$this->linkedPurchase($officer, 'project', $parent, $budget, 'Worker One'), $this->linkedPurchase($officer, 'project', $parent, $budget, 'Worker One')];
                $workflow = app(ProcurementWorkflowService::class);
                foreach ($orders as $order) {
                    $this->purchaseQuotes($order, $officer);
                    $workflow->transition($order, 'submit', $this->purchaseVersion($order), $officer);
                    foreach (['coordinator', 'secretary'] as $stage) {
                        $workflow->decide($order, $stage, $this->purchaseVersion($order, ['decision' => 'accept', 'notes' => 'Checked']), $this->procurementActor());
                    }
                    $resolution = $this->committeeResolution();
                    unset($resolution['resolution_file']);
                    file_put_contents($directory.'/resolution-'.$order->id.'.json', json_encode($resolution, JSON_THROW_ON_ERROR));
                }
                $jobs = [[$orders[0]->id, 'approve', (string) Str::uuid(), '500.00'], [$orders[1]->id, 'approve', (string) Str::uuid(), '500.00']];
            }
            $release = $directory.'/release';
            $php = \Tests\Support\PhpWorker::command();
            foreach ($jobs as $i => [$id,$jobKind,$requestKey,$jobAmount]) {
                $worker = new Process(array_merge($php, [base_path('tests/Support/procurement_worker.php'), $database, (string) $id, (string) $payer->id, $jobKind, $requestKey, $jobAmount, $directory.'/ready-'.$i, $release]), base_path(), ['APP_ENV' => 'testing']);
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
            foreach (ProcurementMovement::all() as $movement) {
                $this->assertSame(1, $movement->transaction()->count());
            }
            if ($case === 'competing_orders') {
                $this->assertSame(0, Transaction::count());
                $this->assertSame(50000, app(ProcurementGuard::class)->reserved('project', $parent->id, $budget));
            } elseif ($case === 'amend_race') {
                $a = $a->fresh();
                $this->assertSame($a->frozen ? 0 : 50000, $a->paidMinor());
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
            if ($resolved && dirname($resolved) === realpath(sys_get_temp_dir()) && str_starts_with(basename($resolved), 'procurement-phase9-')) {
                File::deleteDirectory($resolved);
            }
        }
    }

    public static function cases(): array
    {
        return [['same_key'], ['different_keys'], ['same_fund'], ['refund_cap'], ['amend_race'], ['competing_orders']];
    }
}
