<?php

namespace Database\Seeders;

use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessRoleSeeder extends Seeder
{
    public const PERMISSIONS = ['view', 'manage', 'verify', 'recommend', 'review', 'approve', 'agreements', 'disburse', 'finalize', 'repay', 'monitor', 'close', 'void', 'reports', 'categories'];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::PERMISSIONS as $action) {
                Permission::updateOrCreate(['slug' => 'business.'.$action], ['name' => 'Small Business — '.str($action)->headline(), 'module' => 'business', 'description' => 'Requires View Small Business. Application management does not authorize approval or payment.']);
            }
            foreach (['small-business-officer' => 'Small Business Officer', 'chief-coordinator' => 'Chief Coordinator', 'auditor-reviewer' => 'Auditor / Reviewer'] as $slug => $name) {
                Role::firstOrCreate(['slug' => $slug], ['name' => $name, 'description' => 'Small Business workflow responsibilities.', 'status' => 'active', 'is_system' => true]);
            }
            $roles = [
                'small-business-officer' => ['view', 'manage', 'verify', 'agreements', 'monitor'],
                'implementation-committee' => ['view', 'manage', 'verify', 'monitor'],
                'chief-coordinator' => ['view', 'recommend'], 'general-secretary' => ['view', 'review'],
                'decision-making-committee' => ['view', 'approve', 'close'],
                'treasurer' => ['view', 'disburse', 'finalize', 'repay', 'reports'],
                'auditor' => ['view', 'reports'], 'auditor-reviewer' => ['view', 'reports'],
                'administrator' => self::PERMISSIONS,
            ];
            foreach ($roles as $slug => $actions) {
                Role::where('slug', $slug)->first()?->permissions()->syncWithoutDetaching(Permission::whereIn('slug', array_map(fn ($a) => 'business.'.$a, $actions))->pluck('id'));
            }
        });
    }
}
