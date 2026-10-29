<?php

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundReportingService;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$receipt = json_decode(file_get_contents(base_path('tmp/mysql-test-target.json')), true, flags: JSON_THROW_ON_ERROR);
$database = $receipt['database'] ?? '';
if (!app()->environment('local') || !preg_match('/^foundation_management_test_[0-9]{14}_[a-f0-9]{4}$/', $database) || !($receipt['realistic_seeded'] ?? false)) throw new RuntimeException('Populated disposable QA database required.');
config(['database.connections.qa_source_check' => config('database.connections.mysql')]);
$source = DB::connection('qa_source_check');
config(['database.connections.mysql.database' => $database, 'database.connections.mysql.url' => null]);
DB::purge('mysql');
foreach (['local','private','public'] as $disk) config(['filesystems.disks.'.$disk.'.root' => base_path('storage/framework/testing/qa-browser/'.$disk)]);
$qaEmails = User::pluck('email')->all();
$report = ['database' => $database, 'working_database' => $source->getDatabaseName(), 'working_users' => $source->table('users')->count(),
    'synthetic_users_in_working_database' => $source->table('users')->whereIn('email', $qaEmails)->count(),
    'synthetic_funds_in_working_database' => $source->table('funds')->whereIn('fund_reference', ['FND-GENERAL','FND-EDUCATION','FND-WELFARE','FND-QARD','FND-BUSINESS','FND-PROJECTS'])->count(),
    'qa_counts' => [], 'funds' => [], 'exports' => [], 'verified_document_versions' => 0, 'restored_synthetic_versions' => []];
foreach (['users','members','committees','beneficiaries','funds','donations','expenses','transactions','welfare_applications','welfare_assistances','qard_loans','business_applications','projects','procurements','operation_tasks','operation_meetings','library_documents','report_runs','audit_events'] as $table) $report['qa_counts'][$table] = DB::table($table)->count();
foreach (Fund::get() as $fund) {
    $summary = app(FundReportingService::class)->summary($fund);
    $expected = match ($fund->fund_reference) {
        'FND-GENERAL' => ['232699.75', '11999.75'], 'FND-EDUCATION' => ['82499.75', '10999.75'],
        'FND-WELFARE' => ['125000.00', '0.00'], 'FND-QARD' => ['56000.00', '0.00'],
        'FND-BUSINESS' => ['115000.00', '0.00'], 'FND-PROJECTS' => ['280999.75', '45999.75'], default => null,
    };
    if ($expected && [$summary['balance'], $summary['allocated']] !== $expected) throw new RuntimeException('Unexpected cash or commitment total for '.$fund->name);
    $report['funds'][] = ['name' => $fund->name] + $summary;
}
$sample = DB::table('library_document_versions')->where('status','available')->first();
if ($sample && Storage::disk($sample->disk)->exists($sample->path)) copy(Storage::disk($sample->disk)->path($sample->path), base_path('tmp/qa-sample-document.pdf'));
foreach (DB::table('library_document_versions')->where('status','available')->get() as $version) {
    $disk = Storage::disk($version->disk);
    if (!$disk->exists($version->path) && in_array('--restore-qa-sample', $argv, true)) {
        $fixture = base_path('tmp/qa-sample-document.pdf');
        if (is_file($fixture) && hash_file('sha256', $fixture) === $version->sha256
            && app(App\Domain\Documents\Services\DocumentRegistry::class)->safePath($version->disk, $version->path)) {
            $disk->put($version->path, file_get_contents($fixture));
            $report['restored_synthetic_versions'][] = $version->id;
            app(App\Domain\Audit\Services\AuditRecorder::class)->record('documents', 'qa_fixture_restored', ['subject_id' => $version->document_id,
                'subject_type' => App\Domain\Documents\Models\LibraryDocument::class, 'after' => ['version_id' => $version->id],
                'reason' => 'Restored identical synthetic fixture bytes after an upload-replay acceptance test. No real evidence or ledger record changed.']);
        }
    }
    if (!$disk->exists($version->path) || hash_file('sha256', $disk->path($version->path)) !== $version->sha256) throw new RuntimeException('QA document version '.$version->id.' on '.$version->disk.' is '.(!$disk->exists($version->path) ? 'missing' : 'different from its checksum').'.');
    $report['verified_document_versions']++;
}
if (in_array('--exercise-replay', $argv, true)) {
    $order = App\Domain\Procurement\Models\Procurement::firstOrFail();
    $movement = $order->movements()->where('kind', 'payment')->where('status', 'posted')->firstOrFail();
    $original = $order->documents()->where('kind', 'pay')->where('record_id', $movement->id)->firstOrFail();
    $file = Illuminate\Http\UploadedFile::fake()->createWithContent('sample-payment-receipt.pdf', Storage::disk('private')->get($original->path));
    $input = ['version' => $order->version, 'amount' => $movement->amount, 'payment_date' => $movement->payment_date->toDateString(),
        'payment_method' => $movement->payment_method, 'idempotency_key' => $movement->idempotency_key, 'receipt' => $movement->receipt,
        'notes' => $movement->notes, 'invoice_line_id' => $movement->invoice_line_id, 'evidence' => $file];
    $beforeTransactions = DB::table('transactions')->count();
    $payer = User::findOrFail($movement->recorded_by);
    for ($i = 0; $i < 2; $i++) {
        $replay = app(App\Domain\Procurement\Services\ProcurementPaymentService::class)->record($order, 'pay', $input, $payer);
        if ($replay->id !== $movement->id || !Storage::disk('private')->exists($original->path)) throw new RuntimeException('Replay did not preserve the original movement and evidence.');
    }
    if (DB::table('transactions')->count() !== $beforeTransactions) throw new RuntimeException('A replay created another ledger entry.');
    $report['replay_preserves_original_evidence_and_ledger'] = true;
}
foreach (glob(base_path('tmp/qa-exports/*.xlsx')) ?: [] as $path) {
    $book = PhpOffice\PhpSpreadsheet\IOFactory::load($path);
    $sheet = $book->getActiveSheet();
    if (preg_match('/report-(\d+)\.xlsx$/', $path, $match)) {
        $snapshot = App\Domain\Reports\Models\ReportRun::findOrFail((int) $match[1])->snapshot;
        foreach ($snapshot['metrics'] as $i => $metric) {
            if ((string) $sheet->getCell('A'.($i + 7))->getValue() !== $metric['label'] || (string) $sheet->getCell('B'.($i + 7))->getValue() !== (string) $metric['value']) throw new RuntimeException('XLSX metric differs from its retained snapshot.');
        }
    }
    $report['exports'][] = ['file' => basename($path), 'rows' => $sheet->getHighestRow(), 'columns' => $sheet->getHighestColumn(), 'first_cell' => $sheet->getCell('A1')->getValue()];
    if ($sheet->getHighestRow() < 2) throw new RuntimeException('Export has no data: '.basename($path));
    $book->disconnectWorksheets();
}
file_put_contents(base_path('tmp/qa-populated-verification.json'), json_encode($report, JSON_PRETTY_PRINT));
$seedPath = base_path('tmp/qa-realistic-data.json');
$seed = json_decode(file_get_contents($seedPath), true, flags: JSON_THROW_ON_ERROR);
// Older first-run evidence used a connection purged during the switch. The
// independent source connection above verifies isolation without that ambiguity.
unset($seed['working_database_counts_unchanged']);
$seed['working_database_isolation_verified'] = !$report['synthetic_users_in_working_database'] && !$report['synthetic_funds_in_working_database'];
file_put_contents($seedPath, json_encode($seed, JSON_PRETTY_PRINT));
echo json_encode($report, JSON_PRETTY_PRINT), PHP_EOL;
if ($report['synthetic_users_in_working_database'] || $report['synthetic_funds_in_working_database']) exit(1);
