<?php

use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Transactions\Services\TransactionService;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use App\Domain\Welfare\Assistance\Services\WelfareAssistancePaymentService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
[$script, $database, $action, $sourceId, $actorId, $key, $readyFile, $releaseFile] = $argv;
$directory = realpath(dirname($database));
if (! $directory || dirname($directory) !== realpath(sys_get_temp_dir())
    || ! str_starts_with(basename($directory), 'welfare-phase2-')) {
    throw new RuntimeException('Worker only accepts an isolated Phase 2 temporary database.');
}
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config([
    'database.default' => 'sqlite', 'database.connections.sqlite.database' => $database,
    'database.connections.sqlite.busy_timeout' => 10000,
    'database.connections.sqlite.journal_mode' => 'WAL',
    'database.connections.sqlite.synchronous' => 'OFF',
    'cache.default' => 'array', 'session.driver' => 'array',
]);
DB::purge('sqlite');
$actor = User::findOrFail($actorId);
auth()->setUser($actor);
touch($readyFile);
$deadline = microtime(true) + 30;
while (! file_exists($releaseFile)) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Timed out waiting for the concurrent test start.');
    }
    usleep(10000);
    clearstatcache(true, $releaseFile);
}
try {
    $service = app(WelfareAssistancePaymentService::class);
    if ($action === 'pay') {
        $result = $service->record(WelfareAssistance::findOrFail($sourceId), [
            'amount' => '40.00', 'payment_date' => '2026-10-18', 'payment_method' => 'cash', 'idempotency_key' => $key,
        ], $actor);
        echo $result['duplicate'] ? 'DUPLICATE' : 'POSTED';
    } elseif ($action === 'void') {
        $payment = WelfareAssistancePayment::findOrFail($sourceId);
        echo $service->void($payment->assistance, $payment, 'Concurrent void '.$key, $actor) ? 'VOIDED' : 'ALREADY_VOID';
    } elseif ($action === 'expense') {
        $expense = Expense::findOrFail($sourceId);
        app(TransactionService::class)->createForSource($expense, [
            'fund_id' => $expense->fund_id, 'type' => 'expense', 'description' => 'Competing expense',
            'amount' => $expense->amount, 'direction' => 'debit', 'transaction_date' => $expense->expense_date,
        ]);
        echo 'POSTED';
    } elseif ($action === 'review') {
        $review = app(\App\Domain\Welfare\Assistance\Services\WelfareMonitoringService::class)->recordReview(
            WelfareAssistance::findOrFail($sourceId), [
                'idempotency_key' => $key, 'expected_review_id' => 0,
                'review_date' => date('Y-m-d'), 'outcome' => 'continued',
                'next_review_date' => date('Y-m-d', strtotime('+7 days')), 'notes' => 'Concurrent review check.',
            ], $actor);
        echo $review->wasRecentlyCreated ? 'REVIEWED' : 'DUPLICATE_REVIEW';
    } elseif ($action === 'close') {
        app(\App\Domain\Welfare\Assistance\Services\WelfareMonitoringService::class)->close(
            WelfareAssistance::findOrFail($sourceId), 'Concurrent closure check.', $actor);
        echo 'CLOSED';
    } elseif ($action === 'reminders') {
        app(\App\Domain\Welfare\Assistance\Services\WelfareMonitoringService::class)->refreshReminders(
            WelfareAssistance::findOrFail($sourceId));
        echo 'REMINDERS';
    } else {
        throw new RuntimeException('Unknown concurrency action.');
    }
} catch (ValidationException $exception) {
    if (! isset($exception->errors()['amount']) && ! isset($exception->errors()['status']) && ! isset($exception->errors()['review'])) {
        throw $exception;
    }
    echo 'BLOCKED';
}
