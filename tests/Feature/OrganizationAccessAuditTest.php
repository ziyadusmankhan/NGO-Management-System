<?php

namespace Tests\Feature;

use App\Domain\Members\Models\Member;
use App\Domain\Users\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesWelfareFixtures;
use Tests\TestCase;

class OrganizationAccessAuditTest extends TestCase
{
    use CreatesWelfareFixtures, RefreshDatabase;

    public function test_guest_cannot_create_a_member(): void
    {
        $response=$this->post('/members',['full_name'=>'Unauthorized QA Member','phone'=>'03001234567','status'=>'active']);
        $this->assertSame(0,Member::count(),'An unauthenticated request must not create a Member.');
        $this->assertContains($response->getStatusCode(),[302,401,403]);
        $this->get(route('committees.create'))->assertRedirect(route('login'));
        $this->get(route('settings.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_role_management_cannot_grant_themselves_finance_access(): void
    {
        $viewer=$this->staff(['members.view']);
        $role=$viewer->roles()->firstOrFail();
        $permission=Permission::create(['slug'=>'finance.view','name'=>'Finance View','module'=>'finance']);
        $this->actingAs($viewer)->put(route('users.roles.update',$role),['permissions'=>[$permission->id]]);
        $this->assertFalse($viewer->fresh()->hasPermission('finance.view'),'A user without roles.manage must not grant themselves finance access.');
    }

    public function test_member_write_and_role_pages_reject_authenticated_viewers(): void
    {
        $viewer=$this->staff(['members.view']);
        $this->actingAs($viewer)->get(route('members.create'))->assertForbidden();
        $this->post(route('members.store'),['full_name'=>'Denied Member','phone'=>'03001234567','status'=>'active'])->assertForbidden();
        $this->get(route('users.roles'))->assertForbidden();
        $committee=\App\Domain\Committees\Models\Committee::create(['committee_number'=>'COM-ACCESS','name'=>'Access check committee','type'=>'implementation','status'=>'active']);
        $this->post('/committees/'.$committee->id.'/members',['member_id'=>1])->assertForbidden();
    }

    public function test_authorized_member_creation_and_complete_name_search(): void
    {
        $officer=$this->staff(['members.view','members.create']);
        $this->actingAs($officer)->post(route('members.store'),['full_name'=>'Nadia Rahman Khan','phone'=>'03001234567','status'=>'active'])->assertRedirect();
        $this->get(route('members.index',['search'=>'Nadia Rahman Khan']))->assertOk()->assertSee('Nadia Rahman Khan');
        $this->get(route('members.index',['search'=>'  Nadia   Khan  ']))->assertOk()->assertSee('Nadia Rahman Khan');
    }

    public function test_default_seed_provisions_final_decision_role_and_workflow_permissions(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $committee=\App\Domain\Users\Models\Role::where('slug','decision-making-committee')->firstOrFail();
        foreach (['welfare.applications.approve','qard.approve','business.approve','projects.approve','procurement.approve'] as $slug) {
            $this->assertTrue($committee->permissions()->where('slug',$slug)->exists(), 'Missing final approval permission: '.$slug);
        }
        $officer=\App\Domain\Users\Models\Role::where('slug','welfare-officer')->firstOrFail();
        $this->assertTrue($officer->permissions()->where('slug','welfare.agreements.manage')->exists());
    }
}
