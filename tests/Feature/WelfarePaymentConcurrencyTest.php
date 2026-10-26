<?php

namespace Tests\Feature;

use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Funds\Services\FundReportingService;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use App\Domain\Welfare\Assistance\Services\WelfareAssistancePaymentService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\Support\CreatesWelfareFixtures;
use Tests\TestCase;

class WelfarePaymentConcurrencyTest extends TestCase
{
    use CreatesWelfareFixtures;

    #[DataProvider('races')]
    public function test_simultaneous_financial_actions_are_serialized(string $case): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'welfare-phase2-'.Str::uuid();
        File::makeDirectory($directory);
        $database = $directory.DIRECTORY_SEPARATOR.'concurrency.sqlite';
        touch($database);
        $processes = [];
        try {
            // This disposable database tests locking, not durability across power loss.
            config([
                'database.connections.sqlite.database' => $database,
                'database.connections.sqlite.busy_timeout' => 10000,
                'database.connections.sqlite.journal_mode' => 'WAL',
                'database.connections.sqlite.synchronous' => 'OFF',
            ]);
            DB::purge('sqlite');
            $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
            config(['filesystems.disks.private.root' => $directory.DIRECTORY_SEPARATOR.'evidence']);
            Storage::forgetDisk('private');
            $actor = $this->staff();
            $application = $this->signedApplication($actor);
            $fund = $application->fund;
            $fund->update(['opening_balance' => '50.00']);
            $assistance = $this->legacyAssistance($application, 'approved');
            $assistance->update(['amount' => '40.00']);
            $key = (string) Str::uuid();
            $jobs = [['pay', $assistance->id, $key], ['pay', $assistance->id, $key]];
            if (in_array($case, ['review_twice', 'different_reviews'], true)) {
                $jobs = [['review', $assistance->id, $key], ['review', $assistance->id, $case === 'review_twice' ? $key : (string) Str::uuid()]];
            } elseif ($case === 'close_and_payment' || $case === 'close_and_review') {
                $jobs = [['close', $assistance->id, $key], [$case === 'close_and_payment' ? 'pay' : 'review', $assistance->id, (string) Str::uuid()]];
            } elseif ($case === 'reminders_twice') {
                $assistance->update(['next_review_date' => '2000-01-01']);
                $jobs = [['reminders', $assistance->id, $key], ['reminders', $assistance->id, $key]];
            } elseif (in_array($case, ['different_keys', 'recurring_cap'], true)) {
                $jobs[1][2] = (string) Str::uuid();
                if ($case === 'recurring_cap') {
                    $assistance->update(['frequency' => 'monthly']);
                }
            } elseif ($case === 'same_fund') {
                $other = $this->legacyAssistance($application, 'approved');
                $other->update(['amount' => '40.00']);
                $jobs[1] = ['pay', $other->id, (string) Str::uuid()];
            } elseif ($case === 'expense') {
                $expense = Expense::create([
                    'purchase_kind' => 'service',
                    'expense_number' => 'EXP-RACE', 'fund_id' => $fund->id, 'category' => 'education',
                    'description' => 'Competing expense', 'amount' => '40.00', 'expense_date' => '2026-10-18',
                    'payee_name' => 'School', 'status' => 'approved',
                ]);
                $jobs[1] = ['expense', $expense->id, 'expense'];
            } elseif (in_array($case, ['void_twice', 'void_and_payment'], true)) {
                $payment = app(WelfareAssistancePaymentService::class)->record($assistance, [
                    'amount' => '40.00', 'payment_date' => '2026-10-18', 'payment_method' => 'cash', 'idempotency_key' => $key,
                ], $actor)['payment'];
                $jobs = [['void', $payment->id, 'first'], $case === 'void_twice'
                    ? ['void', $payment->id, 'second'] : ['pay', $assistance->id, (string) Str::uuid()]];
            }
            $release = $directory.DIRECTORY_SEPARATOR.'release';
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
            foreach ($jobs as $i => [$action, $sourceId, $requestKey]) {
                $process = new Process(array_merge($php, [
                    base_path('tests/Support/payment_integrity_worker.php'), $database, $action,
                    (string) $sourceId, (string) $actor->id, $requestKey,
                    $directory.DIRECTORY_SEPARATOR.'ready-'.$i, $release,
                ]), base_path(), ['APP_ENV' => 'testing']);
                $process->setTimeout(45)->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 30;
            while (! file_exists($directory.DIRECTORY_SEPARATOR.'ready-0') || ! file_exists($directory.DIRECTORY_SEPARATOR.'ready-1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Workers failed to become ready: '.implode(' ', array_map(fn ($p) => $p->getErrorOutput().$p->getOutput(), $processes)));
                }
                usleep(10000);
                clearstatcache();
            }
            touch($release);
            $results = [];
            foreach ($processes as $process) {
                $this->assertSame(0, $process->wait(), $process->getErrorOutput().$process->getOutput());
                $results[] = trim($process->getOutput());
            }
            sort($results);
            if ($case === 'review_twice') {
                $this->assertSame(['DUPLICATE_REVIEW', 'REVIEWED'], $results);
                $this->assertDatabaseCount('welfare_assistance_reviews', 1);
            } elseif ($case === 'different_reviews') {
                $this->assertSame(['BLOCKED', 'REVIEWED'], $results);
                $this->assertDatabaseCount('welfare_assistance_reviews', 1);
            } elseif ($case === 'reminders_twice') {
                $this->assertSame(['REMINDERS', 'REMINDERS'], $results);
                $this->assertDatabaseCount('welfare_assistance_reminders', 1);
            } elseif ($case === 'close_and_payment' || $case === 'close_and_review') {
                $this->assertContains($results, [['BLOCKED', 'CLOSED'], ['CLOSED', $case === 'close_and_payment' ? 'POSTED' : 'REVIEWED']]);
                $this->assertSame('closed', $assistance->fresh()->status);
                $this->assertSame(1, $assistance->statusChanges()->where('to_status', 'closed')->count());
                $this->assertSame(0, $assistance->reminders()->whereNull('resolved_at')->count());
                $this->assertSame('0.00', app(FundReportingService::class)->summary($fund)['allocated']);
            } elseif ($case === 'same_key') {
                $this->assertSame(['DUPLICATE', 'POSTED'], $results);
            } elseif ($case === 'void_twice') {
                $this->assertSame(['ALREADY_VOID', 'VOIDED'], $results);
            } elseif ($case === 'void_and_payment') {
                $this->assertContains($results, [['BLOCKED', 'VOIDED'], ['POSTED', 'VOIDED']]);
            } else {
                $this->assertSame(['BLOCKED', 'POSTED'], $results);
            }
            $posted = Transaction::where('status', 'posted')->count();
            $this->assertLessThanOrEqual(1, $posted);
            $this->assertSame($posted === 1 ? '10.00' : '50.00', app(FundBalanceService::class)->balance($fund->fresh()));
            foreach (WelfareAssistancePayment::all() as $payment) {
                $this->assertSame(1, $payment->transaction()->count());
                $this->assertSame($payment->status, $payment->transaction->status);
            }
            if ($case === 'void_twice') {
                $this->assertDatabaseCount('transactions', 1);
                $this->assertDatabaseCount('welfare_assistance_status_changes', 2);
                $this->assertSame('approved', $assistance->fresh()->status);
            }
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            DB::purge('sqlite');
            Storage::forgetDisk('private');
            $resolved = realpath($directory);
            if ($resolved && dirname($resolved) === realpath(sys_get_temp_dir())
                && str_starts_with(basename($resolved), 'welfare-phase2-')) {
                File::deleteDirectory($resolved);
            }
        }
    }

    public static function races(): array
    {
        return [['same_key'], ['different_keys'], ['recurring_cap'], ['same_fund'], ['expense'], ['void_twice'], ['void_and_payment'],
            ['review_twice'], ['different_reviews'], ['close_and_payment'], ['close_and_review'], ['reminders_twice']];
    }
}
