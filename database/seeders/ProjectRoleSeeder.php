<?php

namespace Database\Seeders;

use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectRoleSeeder extends Seeder
{
    public const PERMISSIONS = ['view', 'manage', 'verify', 'recommend', 'review', 'approve', 'pay', 'refund', 'monitor', 'amend', 'close', 'void', 'reports', 'categories'];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::PERMISSIONS as $a) {
                Permission::updateOrCreate(['slug' => 'projects.'.$a], ['name' => 'Projects — '.str($a)->headline(), 'module' => 'projects', 'description' => 'Requires Projects view. Approval and payment remain separate.']);
            }
            foreach (['project-officer' => 'Project Officer', 'chief-coordinator' => 'Chief Coordinator', 'auditor-reviewer' => 'Auditor / Reviewer'] as $slug => $name) {
                Role::firstOrCreate(['slug' => $slug], ['name' => $name, 'status' => 'active', 'is_system' => true]);
            }
            $roles = ['project-officer' => ['view', 'manage', 'verify', 'amend', 'monitor'], 'implementation-committee' => ['view', 'manage', 'verify', 'amend', 'monitor'], 'chief-coordinator' => ['view', 'recommend'], 'general-secretary' => ['view', 'review'], 'decision-making-committee' => ['view', 'approve', 'close'], 'treasurer' => ['view', 'pay', 'refund', 'reports'], 'auditor' => ['view', 'reports'], 'auditor-reviewer' => ['view', 'reports'], 'administrator' => self::PERMISSIONS];
            foreach ($roles as $slug => $actions) {
                Role::where('slug', $slug)->first()?->permissions()->syncWithoutDetaching(Permission::whereIn('slug', array_map(fn ($a) => 'projects.'.$a, $actions))->pluck('id'));
            }
        });
    }
}
