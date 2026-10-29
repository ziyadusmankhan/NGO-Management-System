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
    }

    public function test_user_without_role_management_cannot_grant_themselves_finance_access(): void
    {
        $viewer=$this->staff(['members.view']);
        $role=$viewer->roles()->firstOrFail();
        $permission=Permission::create(['slug'=>'finance.view','name'=>'Finance View','module'=>'finance']);
        $this->actingAs($viewer)->put(route('users.roles.update',$role),['permissions'=>[$permission->id]]);
        $this->assertFalse($viewer->fresh()->hasPermission('finance.view'),'A user without roles.manage must not grant themselves finance access.');
    }
}
