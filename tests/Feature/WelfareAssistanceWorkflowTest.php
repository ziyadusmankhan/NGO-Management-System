<?php

namespace Tests\Feature;

use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Assistance\Services\WelfareAssistanceService;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\CreatesWelfareFixtures;
use Tests\TestCase;

class WelfareAssistanceWorkflowTest extends TestCase
{
    use CreatesWelfareFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_creation_copies_signed_terms_records_actor_and_moves_no_money(): void
    {
        $this->freezeSecond();
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $this->actingAs($actor)->post(route('welfare.assistance.store'), [
            'application_id' => $application->id, 'notes' => 'Follow up', 'next_review_date' => '2026-11-01',
        ])->assertRedirect();

        $assistance = WelfareAssistance::sole();
        $this->assertSame($application->id, $assistance->application_id);
        $this->assertSame($application->beneficiary_id, $assistance->beneficiary_id);
        $this->assertSame($application->fund_id, $assistance->fund_id);
        $this->assertSame('12345.67', $assistance->amount);
        $this->assertSame($application->purpose_details, $assistance->purpose_notes);
        $this->assertSame('education_support', $assistance->assistance_type);
        $this->assertSame('monthly', $assistance->frequency);
        $this->assertTrue($assistance->start_date->equalTo($application->start_date));
        $this->assertTrue($assistance->end_date->equalTo($application->end_date));
        $this->assertTrue($assistance->requested_at->equalTo(now()));
        $this->assertTrue($assistance->has_supporting_document);
        $this->assertSame('pending', $assistance->status);
        $this->assertNull($assistance->approved_by);
        $this->assertNull($assistance->approved_at);
        $this->assertSame('Follow up', $assistance->notes);
        $this->assertDatabaseHas('welfare_assistance_status_changes', [
            'assistance_id' => $assistance->id, 'from_status' => null, 'to_status' => 'pending', 'changed_by' => $actor->id,
        ]);
        $this->assertDatabaseCount('welfare_assistance_payments', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame('100000.00', $application->fund->fresh()->opening_balance);
    }

    #[DataProvider('amounts')]
    public function test_amount_is_copied_without_rounding(string $amount): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor, [], $amount);
        $created = app(WelfareAssistanceService::class)->createFromApplication($application->id, [], $actor);
        $this->assertSame($amount, $created->fresh()->amount);
    }

    public static function amounts(): array
    {
        return [['0.01'], ['12345678901.23'], ['9999999999999.99']];
    }

    #[DataProvider('invalidSources')]
    public function test_invalid_source_is_rejected(string $case): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        match ($case) {
            'test' => $application->forceFill(['is_test' => true])->save(),
            'unapproved' => $application->update(['status' => 'submitted']),
            'no_approval_actor' => $application->update(['approved_by' => null]),
            'no_approval_time' => $application->update(['approved_at' => null]),
            'deleted_application' => $application->delete(),
            'unverified' => $application->beneficiary->update(['verification_status' => 'pending']),
            'unapproved_beneficiary' => $application->beneficiary->update(['approval_status' => 'pending']),
            'deleted_beneficiary' => $application->beneficiary->delete(),
            'missing_fund' => $application->update(['fund_id' => null]),
            'inactive_fund' => $application->fund->update(['status' => 'inactive']),
            'deleted_fund' => $application->fund->delete(),
            'no_agreement' => $application->agreement->delete(),
            'draft', 'prepared', 'cancelled' => $application->agreement->update(['status' => $case]),
            'zero_amount' => $application->agreement->update(['approved_amount' => '0.00']),
            'negative_amount' => $application->agreement->update(['approved_amount' => '-1.00']),
            'no_signature_time' => $application->agreement->update(['beneficiary_signed_at' => null]),
            'no_other_signature' => $application->agreement->update(['organization_signed_at' => null]),
            'no_verifier' => $application->agreement->update(['beneficiary_signature_verified_by' => null]),
            'no_name' => $application->agreement->update(['organization_signatory_name' => null]),
            'same_signatory' => $application->agreement->update(['organization_signatory_name' => $application->agreement->beneficiary_signatory_name]),
            'no_full_signature' => $application->agreement->update(['fully_signed_at' => null]),
            'no_evidence_path' => $application->agreement->update(['organization_document_path' => null]),
            'missing_evidence_file' => Storage::disk('private')->delete($application->agreement->beneficiary_document_path),
            'wrong_evidence_path' => $application->agreement->update(['beneficiary_document_path' => 'public/evidence.pdf']),
        };
        $this->actingAs($actor)->postJson(route('welfare.assistance.store'), ['application_id' => $application->id])
            ->assertUnprocessable()->assertJsonValidationErrors('application_id');
        $this->assertDatabaseCount('welfare_assistances', 0);
        $this->assertDatabaseCount('welfare_assistance_status_changes', 0);
        $this->assertDatabaseCount('transactions', 0);
    }

    public static function invalidSources(): array
    {
        return array_map(fn ($case) => [$case], [
            'test', 'unapproved', 'no_approval_actor', 'no_approval_time', 'deleted_application',
            'unverified', 'unapproved_beneficiary', 'deleted_beneficiary', 'missing_fund', 'inactive_fund', 'deleted_fund',
            'no_agreement', 'draft', 'prepared', 'cancelled', 'zero_amount', 'negative_amount',
            'no_signature_time', 'no_other_signature', 'no_verifier', 'no_name', 'same_signatory',
            'no_full_signature', 'no_evidence_path', 'missing_evidence_file', 'wrong_evidence_path',
        ]);
    }

    #[DataProvider('protectedFields')]
    public function test_authoritative_fields_cannot_be_posted_or_edited(string $field): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $this->actingAs($actor)->postJson(route('welfare.assistance.store'), ['application_id' => $application->id, $field => '999'])
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $assistance = app(WelfareAssistanceService::class)->createFromApplication($application->id, [], $actor);
        $this->putJson(route('welfare.assistance.update', $assistance), [$field => '999'])
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame('12345.67', $assistance->fresh()->amount);
    }

    public static function protectedFields(): array
    {
        return array_map(fn ($field) => [$field], array_merge(WelfareAssistance::INHERITED_FIELDS, ['status', 'approved_by', 'approved_at', 'assistance_number']));
    }

    public function test_manual_creation_cannot_bypass_the_application_requirement(): void
    {
        $actor = $this->staff();
        $this->actingAs($actor)->postJson(route('welfare.assistance.store'), ['amount' => '25.00'])
            ->assertUnprocessable()->assertJsonValidationErrors(['application_id', 'amount']);
        $this->postJson(route('welfare.assistance.store'), ['application_id' => 99999])
            ->assertUnprocessable()->assertJsonValidationErrors('application_id');
        $this->assertDatabaseCount('welfare_assistances', 0);
    }

    public function test_duplicate_and_archived_duplicate_creation_are_rejected(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $this->actingAs($actor);
        $payload = ['application_id' => $application->id];
        $this->postJson(route('welfare.assistance.store'), $payload)->assertRedirect();
        $this->postJson(route('welfare.assistance.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('application_id');
        WelfareAssistance::sole()->delete();
        $this->postJson(route('welfare.assistance.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('application_id');
        $this->assertSame(1, WelfareAssistance::withTrashed()->count());
        $this->assertDatabaseCount('welfare_assistance_status_changes', 1);
    }

    public function test_database_unique_constraint_includes_archived_assistance(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $assistance = app(WelfareAssistanceService::class)->createFromApplication($application->id, [], $actor);
        $assistance->delete();
        $copy = $assistance->replicate();
        $copy->assistance_number = 'DIFFERENT-REFERENCE';
        $this->expectException(UniqueConstraintViolationException::class);
        $copy->save();
    }

    public function test_permissions_are_enforced_for_every_workflow_action(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $assistance = app(WelfareAssistanceService::class)->createFromApplication($application->id, [], $actor);
        $this->postJson(route('welfare.assistance.store'), ['application_id' => $application->id])->assertUnauthorized();
        $viewer = $this->staff(['welfare.assistance.view']);
        $this->actingAs($viewer)->get(route('welfare.assistance.create'))->assertForbidden();
        $this->postJson(route('welfare.assistance.store'), ['application_id' => $application->id])->assertForbidden();
        $this->get(route('welfare.assistance.edit', $assistance))->assertForbidden();
        $this->putJson(route('welfare.assistance.update', $assistance), ['notes' => 'Unauthorized'])->assertForbidden();
        foreach (['approve', 'reject', 'resubmit'] as $action) {
            $this->postJson(route('welfare.assistance.'.$action, $assistance))->assertForbidden();
        }
        $manager = $this->staff(['welfare.assistance.view', 'welfare.assistance.manage']);
        $this->actingAs($manager)->postJson(route('welfare.assistance.approve', $assistance))->assertForbidden();
        $this->postJson(route('welfare.assistance.reject', $assistance))->assertForbidden();
        $this->assertSame('pending', $assistance->fresh()->status);
    }

    public function test_status_transitions_are_explicit_and_audited(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $assistance = app(WelfareAssistanceService::class)->createFromApplication($application->id, [], $actor);
        $this->actingAs($actor)->postJson(route('welfare.assistance.reject', $assistance), ['reason' => 'Needs follow-up'])->assertRedirect();
        $this->assertSame('rejected', $assistance->fresh()->status);
        $this->assertNull($assistance->fresh()->approved_by);
        $this->postJson(route('welfare.assistance.approve', $assistance))->assertUnprocessable();
        $this->putJson(route('welfare.assistance.update', $assistance), ['notes' => 'Follow-up complete'])->assertRedirect();
        $this->assertSame('rejected', $assistance->fresh()->status);
        $this->postJson(route('welfare.assistance.resubmit', $assistance), ['reason' => 'Ready'])->assertRedirect();
        $this->postJson(route('welfare.assistance.approve', $assistance))->assertRedirect();
        $this->assertSame('approved', $assistance->fresh()->status);
        $this->assertSame($actor->id, $assistance->fresh()->approved_by);
        $this->assertNotNull($assistance->fresh()->approved_at);
        $this->postJson(route('welfare.assistance.approve', $assistance))->assertUnprocessable();
        $this->postJson(route('welfare.assistance.reject', $assistance))->assertUnprocessable();
        $this->postJson(route('welfare.assistance.resubmit', $assistance))->assertUnprocessable();
        $this->putJson(route('welfare.assistance.update', $assistance), ['notes' => 'Late edit'])->assertUnprocessable();
        $changes = $assistance->statusChanges()->get();
        $this->assertSame(['pending', 'rejected', 'pending', 'approved'], $changes->pluck('to_status')->all());
        $this->assertSame([null, 'pending', 'rejected', 'pending'], $changes->pluck('from_status')->all());
        $this->assertSame([null, 'Needs follow-up', 'Ready', null], $changes->pluck('reason')->all());
        $this->assertSame([$actor->id], $changes->pluck('changed_by')->unique()->values()->all());
        $this->assertTrue($changes->every(fn ($change) => $change->changed_at !== null));
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('welfare_assistance_payments', 0);
    }

    public function test_approval_and_resubmission_recheck_source_eligibility(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $assistance = app(WelfareAssistanceService::class)->createFromApplication($application->id, [], $actor);
        $application->fund->update(['status' => 'inactive']);
        $this->actingAs($actor)->postJson(route('welfare.assistance.approve', $assistance))->assertUnprocessable();
        $this->postJson(route('welfare.assistance.reject', $assistance))->assertRedirect();
        $this->postJson(route('welfare.assistance.resubmit', $assistance))->assertUnprocessable();
        $application->fund->update(['status' => 'active']);
        $application->agreement->update(['approved_amount' => '1.00']);
        $this->postJson(route('welfare.assistance.resubmit', $assistance))->assertUnprocessable();
        $this->assertSame('rejected', $assistance->fresh()->status);
    }

    public function test_legacy_records_remain_editable_but_cannot_acquire_an_application(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $assistance = $this->legacyAssistance($application);
        $data = ['beneficiary_id' => $application->beneficiary_id, 'fund_id' => $application->fund_id,
            'assistance_type' => 'education_support', 'amount' => '120.25', 'frequency' => 'one_time'];
        $this->actingAs($actor)->putJson(route('welfare.assistance.update', $assistance), $data)->assertRedirect();
        $this->assertSame('120.25', $assistance->fresh()->amount);
        $this->putJson(route('welfare.assistance.update', $assistance), $data + ['application_id' => $application->id])
            ->assertUnprocessable()->assertJsonValidationErrors('application_id');
        $this->assertNull($assistance->fresh()->application_id);
        $this->postJson(route('welfare.assistance.approve', $assistance))->assertRedirect();
        $this->get(route('welfare.assistance.show', $assistance))->assertOk()->assertSee('Legacy assistance');
    }

    public function test_pages_render_links_selector_read_only_terms_and_history(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor, ['request_type' => str_repeat('a', 60)]);
        $this->actingAs($actor)->get(route('welfare.assistance.create', ['application_id' => $application->id]))
            ->assertOk()->assertSee('Create pending assistance')->assertSee('12345.67')->assertDontSee('name="amount"', false);
        $this->get(route('welfare.applications.agreement.show', $application))->assertOk()->assertSee('Create assistance');
        $assistance = app(WelfareAssistanceService::class)->createFromApplication($application->id, [], $actor);
        $this->get(route('welfare.assistance.show', $assistance))->assertOk()
            ->assertSee(route('welfare.applications.show', $application), false)
            ->assertSee(route('welfare.applications.agreement.show', $application), false)->assertSee('Status history');
        $this->get(route('welfare.assistance.edit', $assistance))->assertOk()->assertDontSee('name="amount"', false)->assertSee('name="notes"', false);
        $this->get(route('welfare.assistance.index'))->assertOk()->assertSee(str_repeat('a', 60));
        $this->get(route('welfare.applications.show', $application))->assertOk()->assertSee($assistance->assistance_number);
        $this->get(route('welfare.applications.agreement.show', $application))->assertOk()->assertSee($assistance->assistance_number)->assertDontSee('Create assistance');
        $this->get(route('welfare.assistance.create', ['application_id' => $application->id]))->assertOk()->assertSee('unavailable');
        $assistance->delete();
        $this->get(route('welfare.applications.agreement.show', $application))->assertOk()->assertSee('is archived');
    }

    public function test_flag_migration_classifies_both_reserved_references_and_guards_agreements(): void
    {
        $actor = $this->staff();
        $applications = collect(['APP-00005', 'APP-00010'])->map(fn ($number) => $this->signedApplication($actor, ['application_number' => $number]));
        $migration = require database_path('migrations/2026_10_17_000001_add_test_flag_to_welfare_applications.php');
        $migration->down();
        $migration->up();
        foreach ($applications as $application) {
            $this->assertTrue($application->fresh()->isTestApplication());
            $this->actingAs($actor)->postJson(route('welfare.assistance.store'), ['application_id' => $application->id])->assertUnprocessable();
            $application->agreement->update(['status' => 'draft']);
            $this->postJson(route('welfare.applications.agreement.prepare', $application))->assertUnprocessable();
            $this->get(route('welfare.applications.agreement.print', $application))->assertOk();
        }
        $this->assertDatabaseCount('welfare_assistances', 0);
    }

    public function test_bad_notes_and_dates_are_validated(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $this->actingAs($actor)->postJson(route('welfare.assistance.store'), [
            'application_id' => $application->id, 'notes' => str_repeat('a', 5001), 'next_review_date' => 'invalid',
        ])->assertUnprocessable()->assertJsonValidationErrors(['notes', 'next_review_date']);
        $assistance = app(WelfareAssistanceService::class)->createFromApplication($application->id, [], $actor);
        $this->postJson(route('welfare.assistance.reject', $assistance), ['reason' => str_repeat('a', 5001)])
            ->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->assertSame('pending', $assistance->fresh()->status);
    }

    public function test_creation_and_history_are_rolled_back_together_on_failure(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        DB::statement("CREATE TRIGGER fail_status_insert BEFORE INSERT ON welfare_assistance_status_changes BEGIN SELECT RAISE(ABORT, 'Audit unavailable'); END");
        try {
            app(WelfareAssistanceService::class)->createFromApplication($application->id, [], $actor);
            $this->fail('Audit failure must roll back assistance creation.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Audit unavailable', $e->getMessage());
        } finally {
            DB::statement('DROP TRIGGER fail_status_insert');
        }
        $this->assertDatabaseCount('welfare_assistances', 0);
        $this->assertDatabaseCount('welfare_assistance_status_changes', 0);
    }

    public function test_existing_payment_completion_is_recorded_in_status_history(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor, ['frequency' => 'one_time']);
        $assistance = app(WelfareAssistanceService::class)->createFromApplication($application->id, [], $actor);
        $this->actingAs($actor)->postJson(route('welfare.assistance.approve', $assistance))->assertRedirect();
        $data = ['amount' => '12345.67', 'payment_date' => '2026-10-17', 'payment_method' => 'cash', 'idempotency_key' => 'phase1-completion'];
        $this->postJson(route('welfare.assistance.payments.store', $assistance), $data)->assertRedirect();
        $this->postJson(route('welfare.assistance.payments.store', $assistance), $data)->assertRedirect();
        $this->assertSame('completed', $assistance->fresh()->status);
        $this->assertDatabaseHas('welfare_assistance_status_changes', [
            'assistance_id' => $assistance->id, 'from_status' => 'approved', 'to_status' => 'completed', 'changed_by' => $actor->id,
        ]);
        $this->assertDatabaseCount('welfare_assistance_status_changes', 3);
        $this->assertDatabaseCount('welfare_assistance_payments', 1);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_phase_one_migrations_can_roll_back_and_reapply(): void
    {
        $files = [
            '2026_10_17_000001_add_test_flag_to_welfare_applications.php',
            '2026_10_17_000002_create_welfare_assistance_status_changes_table.php',
            '2026_10_17_000003_align_assistance_type_with_application_request_type.php',
        ];
        foreach (array_reverse($files) as $file) {
            (require database_path('migrations/'.$file))->down();
        }
        foreach ($files as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        app(WelfareAssistanceService::class)->createFromApplication($application->id, [], $actor);
        $this->assertDatabaseCount('welfare_assistances', 1);
        $this->assertDatabaseCount('welfare_assistance_status_changes', 1);
    }

    public function test_rejection_remains_available_when_the_source_application_is_archived(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $assistance = app(WelfareAssistanceService::class)->createFromApplication($application->id, [], $actor);
        $application->delete();
        $this->actingAs($actor)->postJson(route('welfare.assistance.approve', $assistance))->assertUnprocessable();
        $this->postJson(route('welfare.assistance.reject', $assistance))->assertRedirect();
        $this->postJson(route('welfare.assistance.resubmit', $assistance))->assertUnprocessable();
        $this->assertSame('rejected', $assistance->fresh()->status);
    }

    public function test_service_authorization_and_immutable_source_fields_cannot_be_bypassed(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $service = app(WelfareAssistanceService::class);
        $assistance = $service->createFromApplication($application->id, ['amount' => '1.00', 'status' => 'approved'], $actor);
        $service->update($assistance, ['amount' => '2.00', 'application_id' => null, 'status' => 'approved'], $actor);
        $this->assertSame('12345.67', $assistance->fresh()->amount);
        $this->assertSame('pending', $assistance->fresh()->status);
        $this->assertSame($application->id, $assistance->fresh()->application_id);
        $viewer = $this->staff(['welfare.assistance.view']);
        foreach ([
            fn () => $service->createFromApplication($application->id, [], $viewer),
            fn () => $service->update($assistance, ['notes' => 'No'], $viewer),
            fn () => $service->transition($assistance, 'approved', $viewer),
        ] as $action) {
            try {
                $action();
                $this->fail('Permission must be checked inside the service.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
    }

    public function test_inactive_accounts_cannot_create_assistance(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $actor->update(['status' => 'inactive']);
        $this->actingAs($actor)->post(route('welfare.assistance.store'), ['application_id' => $application->id])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('welfare_assistances', 0);
    }

    public function test_links_to_agreements_require_agreement_view_permission(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $assistance = app(WelfareAssistanceService::class)->createFromApplication($application->id, [], $actor);
        $viewer = $this->staff(['welfare.assistance.view']);
        $this->actingAs($viewer)->get(route('welfare.assistance.show', $assistance))->assertOk()
            ->assertDontSee(route('welfare.applications.agreement.show', $application), false);
        $this->get(route('welfare.applications.agreement.show', $application))->assertForbidden();
    }

    public function test_a_failed_status_audit_rolls_back_the_status_change(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $service = app(WelfareAssistanceService::class);
        $assistance = $service->createFromApplication($application->id, [], $actor);
        DB::statement("CREATE TRIGGER fail_status_insert BEFORE INSERT ON welfare_assistance_status_changes BEGIN SELECT RAISE(ABORT, 'Audit unavailable'); END");
        try {
            $service->transition($assistance, 'approved', $actor);
            $this->fail('An approval must roll back if its audit entry fails.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Audit unavailable', $e->getMessage());
        } finally {
            DB::statement('DROP TRIGGER fail_status_insert');
        }
        $this->assertSame('pending', $assistance->fresh()->status);
        $this->assertNull($assistance->fresh()->approved_by);
        $this->assertDatabaseCount('welfare_assistance_status_changes', 1);
    }

    public function test_type_filter_deduplicates_existing_types(): void
    {
        $actor = $this->staff();
        $application = $this->signedApplication($actor);
        $this->legacyAssistance($application);
        $this->legacyAssistance($application);
        $this->actingAs($actor)->get(route('welfare.assistance.index'))->assertOk()
            ->assertViewHas('assistanceTypes', fn ($types) => $types->all() === ['education_support']);
    }
}
