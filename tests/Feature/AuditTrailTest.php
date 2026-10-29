<?php

namespace Tests\Feature;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Audit\Services\AuditHistoryImporter;
use App\Domain\Audit\Services\AuditQuery;
use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Finance\Funds\Models\FundChange;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Procurement\Services\ProcurementPaymentService;
use App\Domain\Projects\Services\ProjectWorkflowService;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use Database\Seeders\AuditRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\CreatesProcurementFixtures;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use CreatesProcurementFixtures,RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_historical_missing_actor_reference_is_preserved(): void
    {
        $event = app(AuditRecorder::class)->record('finance', 'retained_action', ['historical' => true, 'actor_id' => 999999, 'occurred_at' => null]);
        $this->assertSame(999999, $event->actor_id);
        $this->assertNull($event->actor_name);
        $this->assertNull($event->occurred_at);
        $this->assertSame('unknown', $event->actor_kind);
    }

    public function test_upgrade_permissions_are_one_time_and_exports_are_separate(): void
    {
        $p = Permission::firstOrCreate(['slug' => 'audit.view'], ['name' => 'Audit', 'module' => 'audit']);
        $viewer = Role::firstOrCreate(['slug' => 'viewer'], ['name' => 'Viewer', 'status' => 'active']);
        $viewer->permissions()->sync([$p->id]);
        $secretary = Role::firstOrCreate(['slug' => 'general-secretary'], ['name' => 'Secretary', 'status' => 'active']);
        $secretary->permissions()->sync([$p->id]);
        $this->seed(AuditRoleSeeder::class);
        $this->assertFalse($viewer->permissions()->where('slug', 'audit.view')->exists());
        $this->assertFalse($secretary->permissions()->where('slug', 'audit.view')->exists());
        $this->assertSame(2, Role::where('slug', 'auditor-reviewer')->first()->permissions()->where('module', 'audit')->count());
        $viewer->permissions()->attach($p);
        $this->seed(AuditRoleSeeder::class);
        $this->assertTrue($viewer->permissions()->where('slug', 'audit.view')->exists());
        $u = $this->staff(['audit.view']);
        $this->actingAs($u)->get(route('records.audit.index'))->assertOk();
        $this->get(route('records.audit.export'))->assertForbidden();
        $this->actingAs($this->staff([]))->get(route('records.audit.index'))->assertForbidden();
    }

    public function test_password_changes_and_personal_fields_are_not_copied(): void
    {
        $a = $this->staff(['audit.view']);
        $target = $this->staff([]);
        $this->actingAs($a);
        $target->update(['name' => 'Updated account', 'email' => 'private.person@example.com', 'password' => 'secret-value-123']);
        $e = AuditEvent::where('action', 'user_updated')->where('subject_id', $target->id)->latest('id')->firstOrFail();
        $this->assertSame($a->id, $e->actor_id);
        $this->assertTrue($e->after_values['password_changed']);
        $raw = json_encode($e->getAttributes());
        foreach (['secret-value-123', 'private.person@example.com', $target->fresh()->getRawOriginal('password')] as $secret) {
            $this->assertStringNotContainsString($secret, $raw);
        }
        $e = app(AuditRecorder::class)->record('welfare', 'test_redaction', ['after' => ['amount' => '10.25', 'cnic' => '12345-1234567-1', 'medical_history' => 'private condition', 'notes' => 'hidden note', 'bank_account' => '1234567890123456', 'items' => [['password' => 'nested secret', 'amount' => '2.00']]], 'reason' => 'Correction for 12345-1234567-1']);
        $this->assertSame('10.25', $e->after_values['amount']);
        $this->assertArrayNotHasKey('medical_history', $e->after_values);
        $this->assertStringNotContainsString('12345-1234567-1', json_encode($e->getAttributes()));
    }

    public function test_failed_audit_rolls_back_user_and_permission_changes(): void
    {
        $target = $this->staff([]);
        $old = $target->name;
        $role = $target->roles()->firstOrFail();
        $p = Permission::firstOrCreate(['slug' => 'audit.view'], ['name' => 'Audit', 'module' => 'audit']);
        $before = $role->permissions()->pluck('permissions.id')->all();
        $this->mock(AuditRecorder::class)->shouldReceive('record')->andThrow(new \RuntimeException('Audit unavailable'));
        try {
            $target->update(['name' => 'Must roll back']);
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertSame($old, $target->fresh()->name);
        }
        try {
            $role->permissions()->sync([$p->id]);
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertSame($before, $role->permissions()->pluck('permissions.id')->all());
        }
    }

    public function test_missing_installed_audit_storage_blocks_business_changes(): void
    {
        $actor = $this->staff([]);
        $before = $actor->name;
        Schema::drop('audit_events');
        try {
            $actor->update(['name' => 'Must not persist']);
            $this->fail('Expected required audit storage failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Required audit storage is unavailable.', $e->getMessage());
            $this->assertSame($before, $actor->fresh()->name);
        }
    }

    public function test_business_transaction_rollback_leaves_no_success_event(): void
    {
        $a = $this->staff([]);
        $before = AuditEvent::count();
        try {
            DB::transaction(function () use ($a) {
                $a->update(['name' => 'Rollback']);
                throw new \RuntimeException('Later failure');
            });
        } catch (\RuntimeException) {
        }
        $this->assertSame($before, AuditEvent::count());
        $this->assertNotSame('Rollback', $a->fresh()->name);
    }

    public function test_login_failure_logout_and_denied_actions_are_sanitized(): void
    {
        $user = $this->staff([]);
        $user->update(['password' => 'login-test-password']);
        $this->post('/login', ['email' => 'nonexistent-sensitive@example.com', 'password' => 'DO-NOT-LOG'])->assertSessionHasErrors();
        $failed = AuditEvent::where('action', 'login_failed')->latest('id')->firstOrFail();
        $this->assertNull($failed->actor_id);
        $this->assertSame('anonymous', $failed->actor_kind);
        $this->assertStringNotContainsString('nonexistent-sensitive', json_encode($failed->getAttributes()));
        $this->post('/login', ['email' => $user->email, 'password' => 'login-test-password'])->assertRedirect();
        $this->assertSame($user->id, AuditEvent::where('action', 'login')->latest('id')->value('actor_id'));
        $this->get(route('records.audit.index'))->assertForbidden();
        $this->assertSame('blocked', AuditEvent::where('action', 'access_denied')->latest('id')->value('outcome'));
        $this->post('/logout')->assertRedirect('/login');
        $this->assertSame($user->id, AuditEvent::where('action', 'logout')->latest('id')->value('actor_id'));
    }

    public function test_retained_history_import_is_read_only_in_dry_run_and_idempotent(): void
    {
        $actor = $this->procurementActor();
        $p = $this->purchaseDraft($actor);
        $source = FundChange::withoutEvents(fn () => $p->fund->changes()->create(['action' => 'updated', 'before_values' => ['opening_balance' => '900.00'], 'after_values' => ['opening_balance' => '1000.00'], 'changed_by' => $actor->id, 'changed_at' => '2026-01-01 10:00:00']));
        $before = AuditEvent::count();
        $import = app(AuditHistoryImporter::class);
        $dry = $import->run(true);
        $this->assertGreaterThanOrEqual(1, $dry['imported_count']);
        $this->assertSame($before, AuditEvent::count());
        $import->run();
        $e = AuditEvent::where('source_key', 'fund_changes:'.$source->id)->firstOrFail();
        $this->assertTrue($e->historical);
        $this->assertSame('2026-01-01 10:00:00', $e->occurred_at->format('Y-m-d H:i:s'));
        $this->assertSame($actor->id, $e->actor_id);
        $this->assertEmpty($e->context);
        $import->run();
        $this->assertSame(1, AuditEvent::where('source_key', 'fund_changes:'.$source->id)->count());
        $this->assertNotNull($source->fresh());
    }

    public function test_source_key_capture_is_idempotent_inside_an_existing_transaction(): void
    {
        $recorder = app(AuditRecorder::class);
        $first = null;
        $second = null;

        DB::transaction(function () use ($recorder, &$first, &$second): void {
            $data = [
                'source_key' => 'welfare_assistance_status_changes:1',
                'subject_type' => WelfareAssistance::class,
                'subject_id' => 1,
                'subject_reference' => 'WFA-00001',
                'after' => ['to_status' => 'pending'],
            ];
            $first = $recorder->record('welfare', 'status_pending', $data);
            $second = $recorder->record('welfare', 'status_pending', $data);
        });

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, AuditEvent::where('source_key', 'welfare_assistance_status_changes:1')->count());
    }

    public function test_exports_use_fixed_snapshot_safe_strings_and_karachi_dates(): void
    {
        $a = $this->staff(['audit.view', 'audit.export']);
        $this->actingAs($a);
        $e = app(AuditRecorder::class)->record('audit', 'export_fixture', ['occurred_at' => '2026-10-25 19:00:00', 'subject_reference' => '=1+1', 'after' => ['amount' => '10.25']]);
        $this->get(route('records.audit.index', ['from' => '2026-10-26', 'to' => '2026-10-26', 'action' => 'export_fixture']))->assertOk()->assertSee('=1+1');
        $this->assertSame(0, app(AuditQuery::class)->query(['from' => '2026-10-25', 'to' => '2026-10-25', 'action' => 'export_fixture'])->count());
        $r = $this->get(route('records.audit.export', ['action' => 'export_fixture', 'format' => 'xlsx']))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'audit-xlsx-');
        try {
            file_put_contents($path, $r->getContent());
            $book = IOFactory::load($path);
            $sheet = $book->getActiveSheet();
            $this->assertSame(2, $sheet->getHighestRow());
            $this->assertSame('=1+1', $sheet->getCell('G2')->getValue());
            $this->assertSame('s', $sheet->getCell('G2')->getDataType());
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
        $export = AuditEvent::where('action', 'export_generated')->latest('id')->firstOrFail();
        $this->assertSame(1, $export->after_values['export_count']);
        $this->assertLessThan($export->id, $export->after_values['snapshot_id']);
        $this->get(route('records.audit.export', ['action' => 'export_fixture', 'format' => 'print']))->assertOk()->assertSee('10.25');
    }

    public function test_financial_replay_has_one_posted_audit_event_with_one_ledger_reference(): void
    {
        $a = $this->procurementActor();
        $p = $this->purchaseApprove($this->purchaseDraft($a), $a);
        $l = $this->purchaseInvoice($p, $a);
        $payer = $this->procurementActor();
        $data = $this->purchasePayment($p, $l, '10.25');
        $service = app(ProcurementPaymentService::class);
        $m = $service->record($p, 'pay', $data, $payer);
        $service->record($p, 'pay', $data, $payer);
        $events = AuditEvent::where('module', 'procurement')->where('action', 'pay_posted')->where('subject_id', $p->id)->get();
        $this->assertCount(1, $events);
        $this->assertSame($payer->id, $events[0]->actor_id);
        $this->assertSame('10.25', $events[0]->after_values['amount']);
        $this->assertSame($m->ledger()->id, $events[0]->context['ledger_entries'][0]['id']);
    }

    public function test_import_dry_run_matches_real_exclusions_for_procurement_linked_payments(): void
    {
        $a = $this->procurementActor();
        $data = $this->projectData($a);
        $data['items'][0]['kind'] = 'purchase';
        $data['items'][0]['price_checks'] = [['supplier' => 'Worker One', 'amount' => '500.00', 'reference' => 'Q1'], ['supplier' => 'Other', 'amount' => '550.00', 'reference' => 'Q2']];
        $parent = $this->approveProject(app(ProjectWorkflowService::class)->save(null, $data, $a), $a);
        $item = $parent->budgets($parent->approved_revision)->first()->budget_item_id;
        $order = $this->purchaseApprove($this->linkedPurchase($a, 'project', $parent, $item, 'Worker One'), $a);
        $line = $this->purchaseInvoice($order, $a);
        $movement = app(ProcurementPaymentService::class)->record($order, 'pay', $this->purchasePayment($order, $line, '10.25'), $this->procurementActor());
        $parentEvent = $parent->events()->where('action', 'payment_posted')->latest('id')->firstOrFail();
        $this->assertSame($movement->source_id, $parentEvent->after_values['movement_id']);
        $this->assertFalse(AuditEvent::where('source_key', 'project_events:'.$parentEvent->id)->exists());
        $import = app(AuditHistoryImporter::class);
        $dry = $import->run(true);
        $real = $import->run();
        $this->assertSame($dry, $real);
        $this->assertGreaterThanOrEqual(1, $real['excluded_count']);
        $this->assertSame(1, AuditEvent::where('module', 'procurement')->where('action', 'pay_posted')->where('subject_id', $order->id)->count());
    }

    public function test_history_survives_archival_and_has_no_edit_endpoint(): void
    {
        $a = $this->staff(['audit.view']);
        $this->actingAs($a);
        $e = app(AuditRecorder::class)->record('audit', 'retention_test');
        $name = $a->name;
        $a->delete();
        $this->assertSame($name, $e->fresh()->actor_name);
        try {
            $e->update(['summary' => 'Rewrite']);
            $this->fail('Expected immutable record');
        } catch (\LogicException) {
            $this->assertNotSame('Rewrite', $e->fresh()->summary);
        }
        try {
            $e->delete();
            $this->fail('Expected deletion rejection');
        } catch (\LogicException) {
            $this->assertNotNull($e->fresh());
        }
    }

    public function test_audit_access_does_not_grant_project_document_access(): void
    {
        $actor = $this->procurementActor();
        $p = $this->draftProject($actor);
        $reader = $this->staff(['audit.view']);
        $this->actingAs($reader);
        $e = AuditEvent::where('subject_type', get_class($p))->where('subject_id', $p->id)->firstOrFail();
        $this->get(route('records.audit.show', $e))->assertOk()->assertDontSee('Open source record');
        $this->get(route('projects.show', $p))->assertForbidden();
    }

    public function test_audit_failure_rolls_back_payment_ledger_and_private_evidence(): void
    {
        $a = $this->procurementActor();
        $p = $this->purchaseApprove($this->purchaseDraft($a), $a);
        $line = $this->purchaseInvoice($p, $a);
        $payer = $this->procurementActor();
        $files = Storage::disk('private')->allFiles();
        $this->mock(AuditRecorder::class)->shouldReceive('record')->andThrow(new \RuntimeException('Audit write failed'));
        try {
            app(ProcurementPaymentService::class)->record($p, 'pay', $this->purchasePayment($p, $line, '10.25'), $payer);
            $this->fail('Expected failure');
        } catch (\RuntimeException) {
            $this->assertSame(0, $p->movements()->count());
            $this->assertSame(0, Transaction::count());
            $this->assertSame($files, Storage::disk('private')->allFiles());
        }
    }

    public function test_security_capture_failure_does_not_prevent_logout_and_is_reported(): void
    {
        $a = $this->staff([]);
        $this->actingAs($a);
        $original = app()->storagePath();
        $folder = sys_get_temp_dir().'/audit-health-'.bin2hex(random_bytes(8));
        mkdir($folder.'/app', 0777, true);
        app()->useStoragePath($folder);
        $recorder = \Mockery::mock(AuditRecorder::class)->makePartial();
        $recorder->shouldReceive('record')->andThrow(new \RuntimeException('Private payload must not be logged'));
        $this->app->instance(AuditRecorder::class, $recorder);
        try {
            $this->post('/logout')->assertRedirect('/login');
            $this->assertGuest();
            $this->assertFileExists($folder.'/app/audit-health-failure.json');
            $this->assertStringNotContainsString('Private payload', file_get_contents($folder.'/app/audit-health-failure.json'));
        } finally {
            app()->useStoragePath($original);
            $resolved = realpath($folder);
            if (! $resolved || dirname($resolved) !== realpath(sys_get_temp_dir()) || ! str_starts_with(basename($resolved), 'audit-health-')) {
                throw new \RuntimeException('Unsafe test cleanup path');
            }foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $f) {
                if ($f->isDir()) {
                    rmdir($f->getPathname());
                } else {
                    unlink($f->getPathname());
                }
            }rmdir($folder);
        }
    }

    public function test_export_limit_and_server_pagination(): void
    {
        $a = $this->staff(['audit.view', 'audit.export']);
        $rows = [];
        for ($i = 0; $i < 10001; $i++) {
            $rows[] = ['event_uuid' => (string) Str::uuid(), 'module' => 'audit', 'action' => 'bulk_fixture', 'outcome' => 'success', 'actor_kind' => 'system', 'summary' => 'Fixture', 'captured_at' => '2026-10-26 00:00:00', 'occurred_at' => '2026-10-26 00:00:00'];
            if (count($rows) === 400) {
                DB::table('audit_events')->insert($rows);
                $rows = [];
            }
        }
        if ($rows) {
            DB::table('audit_events')->insert($rows);
        }
        $this->actingAs($a)->get(route('records.audit.index', ['action' => 'bulk_fixture']))->assertOk()->assertViewHas('events', fn ($events) => $events->count() === 30 && $events->total() === 10001);
        $this->get(route('records.audit.export', ['action' => 'bulk_fixture', 'format' => 'xlsx']))->assertSessionHasErrors('export');
        $this->assertSame(0, AuditEvent::where('action', 'export_generated')->count());
    }
}
