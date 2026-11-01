<?php

namespace Tests\Feature;

use App\Domain\Documents\Models\LibraryDocument;
use App\Domain\Documents\Services\DocumentAccess;
use App\Domain\Users\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesProcurementFixtures;
use Tests\TestCase;

class ReviewHardeningTest extends TestCase
{
    use CreatesProcurementFixtures, RefreshDatabase;

    private function administrator()
    {
        $user = $this->staff([]);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator', 'status' => 'active']));

        return $user;
    }

    private function profile($user, array $extra = []): array
    {
        return array_merge(['name' => $user->name, 'email' => $user->email, 'status' => 'active'], $extra);
    }

    public function test_delegated_manager_cannot_escalate_self_or_create_an_administrator(): void
    {
        $admin = $this->administrator();
        $manager = $this->staff(['users.manage', 'users.assign-roles']);
        $role = $admin->roles()->where('slug', 'administrator')->first();
        $this->actingAs($manager)->put(route('users.update', $manager), $this->profile($manager, ['roles' => [$role->id]]))->assertForbidden();
        $this->post(route('users.store'), ['name' => 'New admin', 'email' => 'new@example.test', 'password' => 'long-test-password', 'password_confirmation' => 'long-test-password', 'status' => 'active', 'roles' => [$role->id]])->assertForbidden();
        $this->assertFalse($manager->fresh()->hasRole('administrator'));
        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
    }

    public function test_delegated_manager_cannot_edit_or_archive_another_administrator(): void
    {
        $admin = $this->administrator();
        $manager = $this->staff(['users.manage']);
        $this->actingAs($manager)->put(route('users.update', $admin), $this->profile($admin, ['email' => 'takeover@example.test']))->assertForbidden();
        $this->delete(route('users.destroy', $admin))->assertForbidden();
        $this->assertSame($admin->email, $admin->fresh()->email);
    }

    public function test_profile_management_preserves_roles_and_assignment_requires_separate_authority(): void
    {
        $manager = $this->staff(['users.manage']);
        $target = $this->staff(['documents.view']);
        $ids = $target->roles()->pluck('roles.id')->all();
        $this->actingAs($manager)->put(route('users.update', $target), $this->profile($target, ['name' => 'Updated name']))->assertRedirect();
        $this->assertSame($ids, $target->roles()->pluck('roles.id')->all());
        $this->put(route('users.update', $target), $this->profile($target, ['roles' => []]))->assertForbidden();
        $limited = $this->staff(['users.manage', 'users.assign-roles']);
        $this->actingAs($limited)->put(route('users.update', $target), $this->profile($target, ['roles' => []]))->assertForbidden();
        $admin = $this->administrator();
        $this->actingAs($admin)->put(route('users.update', $target), $this->profile($target, ['roles' => []]))->assertRedirect();
        $this->assertSame(0, $target->roles()->count());
    }

    public function test_delegated_role_editor_cannot_edit_own_or_administrator_permissions(): void
    {
        $admin = $this->administrator();
        $editor = $this->staff(['roles.manage']);
        $this->actingAs($editor)->put(route('users.roles.update', $editor->roles()->first()), ['permissions' => []])->assertForbidden();
        $this->put(route('users.roles.update', $admin->roles()->where('slug', 'administrator')->first()), ['permissions' => []])->assertForbidden();
    }

    public function test_admin_can_assign_another_administrator_but_cannot_change_own_roles(): void
    {
        $admin = $this->administrator();
        $other = $this->staff([]);
        $roles = $admin->roles()->where('slug', 'administrator')->pluck('roles.id')->all();
        $this->actingAs($admin)->put(route('users.update', $other), $this->profile($other, ['roles' => $roles]))->assertRedirect();
        $this->assertTrue($other->fresh()->hasRole('administrator'));
        $this->put(route('users.update', $admin), $this->profile($admin, ['roles' => []]))->assertForbidden();
    }

    public function test_document_visibility_is_lazy_sql_and_counts_use_the_same_policy(): void
    {
        $owner = $this->staff(['documents.view']);
        $other = $this->staff(['documents.view']);
        $d = LibraryDocument::create(['number' => 'DOC-SQL', 'title' => 'Private policy', 'category' => 'Policy', 'owner_id' => $owner->id]);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $query = app(DocumentAccess::class)->visible($other);
        $this->assertFalse(collect(DB::getQueryLog())->contains(fn ($q) => str_contains($q['query'], 'from "library_documents"')));
        $this->assertSame(0, $query->count());
        $d->roles()->attach($other->roles()->first());
        $this->assertSame([$d->id], app(DocumentAccess::class)->visible($other)->limit(6)->pluck('id')->all());
        $other->roles()->detach();
        $this->assertFalse(app(DocumentAccess::class)->allows($d, $other));
        DB::disableQueryLog();
    }

    public function test_scheduler_health_rejects_missing_stale_and_failed_runs(): void
    {
        $this->artisan('operations:scheduler-health')->assertExitCode(1);
        Cache::forever('operations.scheduler.success', now()->timestamp);
        $this->artisan('operations:scheduler-health')->assertExitCode(0);
        Cache::forever('operations.scheduler.tasks', ['failed-task' => now()->timestamp]);
        $this->artisan('operations:scheduler-health')->assertExitCode(1);
        Cache::forget('operations.scheduler.tasks');
        Cache::forever('operations.scheduler.success', now()->subMinutes(4)->timestamp);
        $this->artisan('operations:scheduler-health')->assertExitCode(1);
    }

    public function test_linked_document_sql_preserves_parent_and_procurement_restrictions(): void
    {
        $actor = $this->procurementActor();
        $project = $this->draftProject($actor);
        $order = $this->purchaseDraft($actor);
        $onlyProject = $this->staff(['projects.view']);
        $onlyProcurement = $this->staff(['procurement.view']);
        $both = $this->staff(['procurement.view', 'projects.view']);
        $office = LibraryDocument::create(['number' => 'DOC-OFFICE', 'title' => 'Office evidence', 'category' => 'Other', 'parent_type' => 'procurement', 'parent_id' => $order->id]);
        $access = app(DocumentAccess::class);
        $this->assertTrue($access->allows($office, $onlyProcurement));
        $this->assertFalse($access->allows($office, $onlyProject));
        $order->update(['parent_type' => 'project', 'parent_id' => $project->id]);
        $this->assertFalse($access->allows($office, $onlyProcurement));
        $this->assertTrue($access->allows($office, $both));
        $source = LibraryDocument::where('parent_type', 'projects')->where('parent_id', $project->id)->where('authoritative', true)->firstOrFail();
        $this->assertTrue($access->allows($source, $onlyProject));
        $this->assertFalse($access->allows($source, $onlyProcurement));
        $source->update(['source_key' => 'projects:99999999']);
        $this->assertFalse($access->allows($source, $both));
    }

    public function test_production_mysql_config_includes_ca_and_certificate_verification(): void
    {
        if (! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('pdo_mysql is required to inspect TLS options.');
        }
        putenv('MYSQL_ATTR_SSL_CA=/tmp/test-ca.pem');
        try {
            $config = require base_path('config/database.php');
            $this->assertSame('/tmp/test-ca.pem', $config['connections']['mysql']['options'][\PDO::MYSQL_ATTR_SSL_CA]);
            $this->assertTrue($config['connections']['mysql']['options'][\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]);
        } finally {
            putenv('MYSQL_ATTR_SSL_CA');
        }
    }

    public function test_scheduler_wrapper_keeps_task_failures_until_that_task_succeeds(): void
    {
        $fail = true;
        \Illuminate\Support\Facades\Schedule::call(function () use (&$fail) {
            if ($fail) { throw new \RuntimeException('Synthetic scheduled task failure'); }
        })->name('review-test-task')->everyMinute();
        $this->artisan('operations:scheduler-run')->assertExitCode(1);
        $this->assertNotEmpty(Cache::get('operations.scheduler.tasks'));
        $fail = false;
        $this->travel(1)->minutes();
        $this->artisan('operations:scheduler-run')->assertExitCode(0);
        $this->assertEmpty(Cache::get('operations.scheduler.tasks'));
        $this->artisan('operations:scheduler-health')->assertExitCode(0);
    }
}
