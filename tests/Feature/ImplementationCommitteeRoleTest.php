<?php

namespace Tests\Feature;

use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Database\Seeders\ImplementationCommitteeRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesWelfareFixtures;
use Tests\TestCase;

class ImplementationCommitteeRoleTest extends TestCase
{
    use CreatesWelfareFixtures, RefreshDatabase;

    public function test_role_is_repeatable_and_preserves_existing_assignments(): void
    {
        $unrelated = $this->staff(['users.view']);
        $before = $unrelated->roles()->first()->permissions()->pluck('slug')->all();
        $this->seed(ImplementationCommitteeRoleSeeder::class);
        $role = Role::where('slug', 'implementation-committee')->sole();
        $this->assertSame('Implementation / Administration Committee', $role->name);
        $this->assertSame('active', $role->status);
        $this->assertEqualsCanonicalizing([
            'welfare.view', 'welfare.assistance.view', 'welfare.applications.recommend',
        ], $role->permissions()->pluck('slug')->all());
        $custom = Permission::create(['slug' => 'custom.example', 'name' => 'Custom example', 'module' => 'custom']);
        $role->permissions()->attach($custom);
        $unrelated->roles()->attach($role);
        $this->seed(ImplementationCommitteeRoleSeeder::class);
        $this->assertSame(1, Role::where('slug', 'implementation-committee')->count());
        $this->assertSame(4, $role->permissions()->count());
        $this->assertSame(1, $role->users()->count());
        $this->assertSame($before, $unrelated->roles()->where('roles.id', '!=', $role->id)->first()->permissions()->pluck('slug')->all());
    }

    public function test_role_can_recommend_but_cannot_review_approve_or_manage_payments(): void
    {
        Storage::fake('private');
        $this->seed(ImplementationCommitteeRoleSeeder::class);
        $submitter = $this->staff();
        $reviewer = $this->staff([]);
        $reviewer->roles()->attach(Role::where('slug', 'implementation-committee')->sole());
        $application = $this->signedApplication($submitter, ['status' => 'recommendation', 'submitted_by' => $submitter->id]);
        $application->verifications()->create([
            'beneficiary_id' => $application->beneficiary_id,
            'workflow_status' => 'completed', 'completed_at' => now(), 'verified_by' => $submitter->id,
        ]);
        $this->actingAs($reviewer)->get(route('welfare.applications.show', $application))->assertOk();
        $this->postJson(route('welfare.applications.implementation-decision', $application), [
            'decision' => 'approve', 'recommended_amount' => '1000.25', 'notes' => 'Field verification reviewed.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('general_secretary_review', $application->fresh()->status);
        $this->assertDatabaseHas('welfare_application_reviews', [
            'application_id' => $application->id, 'stage' => 'implementation_committee', 'decided_by' => $reviewer->id,
        ]);
        foreach (['welfare.applications.review', 'welfare.applications.approve', 'welfare.assistance.manage', 'welfare.assistance.approve', 'users.manage', 'roles.manage'] as $permission) {
            $this->assertFalse($reviewer->hasPermission($permission));
        }
        $this->postJson(route('welfare.applications.committee-decision', $application), [])->assertForbidden();
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_recommender_cannot_decide_their_own_application_or_skip_verification(): void
    {
        Storage::fake('private');
        $this->seed(ImplementationCommitteeRoleSeeder::class);
        $reviewer = $this->staff([]);
        $reviewer->roles()->attach(Role::where('slug', 'implementation-committee')->sole());
        $application = $this->signedApplication($reviewer, ['status' => 'recommendation', 'submitted_by' => $reviewer->id]);
        $payload = ['decision' => 'approve', 'recommended_amount' => '100.00', 'notes' => 'Review'];
        $this->actingAs($reviewer)->postJson(route('welfare.applications.implementation-decision', $application), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('decision');
        $application->update(['submitted_by' => $this->staff()->id]);
        $this->postJson(route('welfare.applications.implementation-decision', $application), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertDatabaseCount('welfare_application_reviews', 0);
    }

    public function test_admin_can_see_role_in_roles_and_user_forms(): void
    {
        $this->seed(ImplementationCommitteeRoleSeeder::class);
        $admin = $this->staff(['users.view', 'users.manage', 'roles.manage']);
        $this->actingAs($admin)->get(route('users.roles'))->assertOk()->assertSee('Implementation / Administration Committee');
        $this->get(route('users.create'))->assertOk()->assertSee('Implementation / Administration Committee');
        $this->get(route('users.edit', $admin))->assertOk()->assertSee('Implementation / Administration Committee');
    }
}
