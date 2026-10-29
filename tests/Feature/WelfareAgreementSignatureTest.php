<?php

namespace Tests\Feature;

use App\Domain\Users\Models\User;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesWelfareFixtures;
use Tests\Support\ValidUpload;
use Tests\TestCase;

class WelfareAgreementSignatureTest extends TestCase
{
    use CreatesWelfareFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    private function preparedApplication(User $actor): WelfareApplication
    {
        $application = $this->signedApplication($actor);
        $application->agreement->update([
            'status' => 'prepared', 'fully_signed_at' => null,
            'beneficiary_signatory_name' => null, 'beneficiary_signed_at' => null,
            'beneficiary_document_path' => null, 'beneficiary_signature_verified_by' => null,
            'organization_signatory_name' => null, 'organization_signed_at' => null,
            'organization_document_path' => null, 'organization_signature_verified_by' => null,
        ]);

        return $application->fresh('agreement');
    }

    private function evidence(string $name = 'Beneficiary signer'): array
    {
        return [
            'signatory_name' => $name, 'confirmed' => '1',
            'document' => ValidUpload::pdf('signed.pdf', 200),
        ];
    }

    public function test_both_signatures_are_saved_with_private_evidence_and_visible_success(): void
    {
        $actor = $this->staff();
        $application = $this->preparedApplication($actor);
        $show = route('welfare.applications.agreement.show', $application);
        foreach (['beneficiary' => 'Beneficiary signer', 'organization' => 'Organization signer'] as $party => $name) {
            $this->actingAs($actor)->from($show)->post(route('welfare.applications.agreement.signature', [$application, $party]), $this->evidence($name))
                ->assertRedirect($show)->assertSessionHasNoErrors()->assertSessionHas('success');
            $agreement = $application->agreement()->first();
            $this->assertSame($name, $agreement->{$party.'_signatory_name'});
            $this->assertSame($actor->id, $agreement->{$party.'_signature_verified_by'});
            $this->assertNotNull($agreement->{$party.'_signed_at'});
            Storage::disk('private')->assertExists($agreement->{$party.'_document_path'});
            $this->assertSame($party === 'beneficiary' ? 'prepared' : 'signed', $agreement->status);
        }
        $this->get($show)->assertOk()->assertSee('Both signatures and their evidence have been recorded.');
        $this->assertNotNull($application->agreement()->first()->fully_signed_at);
        $this->assertDatabaseCount('welfare_assistances', 0);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_reserved_application_shows_the_block_and_does_not_offer_signature_forms(): void
    {
        $actor = $this->staff();
        $application = $this->preparedApplication($actor);
        $application->forceFill(['application_number' => 'APP-00005', 'is_test' => true])->save();
        $show = route('welfare.applications.agreement.show', $application);
        $this->actingAs($actor)->get($show)->assertOk()->assertSee('reserved for workflow testing')
            ->assertDontSee('Record Beneficiary Signature')->assertDontSee('Record Organization Signature');
        $this->from($show)->post(route('welfare.applications.agreement.signature', [$application, 'beneficiary']), $this->evidence())
            ->assertRedirect($show)->assertSessionHasErrors('agreement');
        $this->get($show)->assertOk()->assertSee('The agreement action could not be completed.')
            ->assertSee('reserved for workflow testing');
        $this->assertNull($application->agreement()->first()->beneficiary_signed_at);
    }

    public function test_failed_upload_displays_error_and_keeps_name_only_in_submitted_form(): void
    {
        $actor = $this->staff();
        $application = $this->preparedApplication($actor);
        $show = route('welfare.applications.agreement.show', $application);
        $this->actingAs($actor)->from($show)->post(route('welfare.applications.agreement.signature', [$application, 'beneficiary']), [
            'signatory_name' => 'Keep this name', 'confirmed' => '1', 'signature_party' => 'organization',
        ])->assertRedirect($show)->assertSessionHasErrors('document');
        $response = $this->get($show)->assertOk()->assertSee('Select the signed agreement evidence file.')
            ->assertSee('select the evidence file again');
        $this->assertSame(1, substr_count($response->getContent(), 'value="Keep this name"'));
        $this->assertNull($application->agreement()->first()->beneficiary_signed_at);
    }

    public function test_oversize_and_invalid_file_uploads_are_explained(): void
    {
        $actor = $this->staff();
        $application = $this->preparedApplication($actor);
        $show = route('welfare.applications.agreement.show', $application);
        $this->actingAs($actor);
        foreach ([
            [UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf'), 'The evidence file must be no larger than 10 MB.'],
            [UploadedFile::fake()->create('wrong.txt', 1, 'text/plain'), 'The evidence must be a PDF, JPG or PNG file.'],
        ] as [$file, $message]) {
            $this->from($show)->post(route('welfare.applications.agreement.signature', [$application, 'beneficiary']), [
                'signatory_name' => 'Signer', 'confirmed' => '1', 'document' => $file,
            ])->assertRedirect($show)->assertSessionHasErrors('document');
            $this->get($show)->assertOk()->assertSee($message);
        }
    }

    public function test_submitter_restriction_is_visible_before_submitting(): void
    {
        $actor = $this->staff();
        $application = $this->preparedApplication($actor);
        $application->update(['submitted_by' => $actor->id]);
        $this->actingAs($actor)->get(route('welfare.applications.agreement.show', $application))
            ->assertOk()->assertSee('Ask another authorized staff member')->assertDontSee('Record Beneficiary Signature');
        $this->postJson(route('welfare.applications.agreement.signature', [$application, 'beneficiary']), $this->evidence())
            ->assertUnprocessable()->assertJsonValidationErrors('agreement');
    }

    public function test_duplicate_signature_and_unauthorized_submissions_are_rejected(): void
    {
        $actor = $this->staff();
        $application = $this->preparedApplication($actor);
        $url = route('welfare.applications.agreement.signature', [$application, 'beneficiary']);
        $this->actingAs($actor)->post($url, $this->evidence())->assertRedirect()->assertSessionHasNoErrors();
        $path = $application->agreement()->first()->beneficiary_document_path;
        $this->postJson($url, $this->evidence())->assertUnprocessable()->assertJsonValidationErrors('agreement');
        $this->assertSame($path, $application->agreement()->first()->beneficiary_document_path);
        $this->actingAs($this->staff(['welfare.agreements.view']))->postJson($url, $this->evidence())->assertForbidden();
    }
}
