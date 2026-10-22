<?php

namespace Tests\Support;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait CreatesWelfareFixtures
{
    protected function staff(array $permissions = ['welfare.reviews.view', 'welfare.reviews.manage', 'welfare.reviews.close', 'welfare.assistance.view', 'welfare.assistance.manage', 'welfare.assistance.approve', 'welfare.agreements.view', 'welfare.agreements.manage']): User
    {
        $key = (string) Str::uuid();
        $user = User::create(['name' => 'Workflow officer', 'email' => $key.'@example.test', 'password' => 'test-password', 'status' => 'active']);
        $role = Role::create(['name' => $key, 'slug' => $key, 'status' => 'active']);
        foreach ($permissions as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => 'welfare']);
            $role->permissions()->attach($permission);
        }
        $user->roles()->attach($role);

        return $user;
    }

    protected function signedApplication(User $actor, array $attributes = [], string $amount = '12345.67'): WelfareApplication
    {
        $key = Str::random(10);
        $beneficiary = Beneficiary::create([
            'beneficiary_number' => 'BEN-'.$key, 'full_name' => 'Applicant '.$key,
            'category' => 'student', 'verification_status' => 'verified', 'approval_status' => 'approved',
        ]);
        $fund = Fund::create(['fund_reference' => 'FND-'.$key, 'name' => 'Education fund', 'purpose' => 'education', 'status' => 'active', 'opening_balance' => '100000.00']);
        $application = WelfareApplication::create(array_merge([
            'application_number' => 'APP-'.$key, 'beneficiary_id' => $beneficiary->id,
            'fund_id' => $fund->id, 'category' => 'education', 'request_type' => 'education_support',
            'requested_amount' => '15000.00', 'recommended_amount' => '14000.00',
            'purpose_details' => 'School tuition and books', 'frequency' => 'monthly',
            'start_date' => '2026-10-01', 'end_date' => '2027-09-30', 'status' => 'approved',
            'approved_by' => $actor->id, 'approved_at' => now(), 'has_supporting_document' => true,
        ], $attributes));
        $agreement = $application->agreement()->create([
            'agreement_number' => 'AGR-'.$key, 'approved_amount' => $amount, 'status' => 'signed',
            'terms' => 'Approved education support', 'created_by' => $actor->id,
            'prepared_by' => $actor->id, 'prepared_at' => now(), 'fully_signed_at' => now(),
            'beneficiary_signed_at' => now(), 'organization_signed_at' => now(),
            'beneficiary_signatory_name' => 'Applicant '.$key, 'organization_signatory_name' => 'Foundation officer',
            'beneficiary_signature_verified_by' => $actor->id, 'organization_signature_verified_by' => $actor->id,
        ]);
        foreach (['beneficiary', 'organization'] as $party) {
            $path = "welfare/agreements/{$agreement->id}/{$party}/signed.pdf";
            Storage::disk('private')->put($path, 'Test signature evidence');
            $agreement->update([$party.'_document_path' => $path]);
        }

        return $application->fresh(['beneficiary', 'fund', 'agreement']);
    }

    protected function legacyAssistance(WelfareApplication $application, string $status = 'pending'): WelfareAssistance
    {
        return WelfareAssistance::create([
            'assistance_number' => 'LEGACY-'.Str::random(10), 'beneficiary_id' => $application->beneficiary_id,
            'fund_id' => $application->fund_id, 'assistance_type' => 'education_support',
            'amount' => '100.25', 'frequency' => 'one_time', 'status' => $status,
        ]);
    }
}
