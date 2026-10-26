<?php

namespace Database\Seeders;

use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcurementRoleSeeder extends Seeder
{
    public const PERMISSIONS = ['view', 'manage', 'suppliers', 'quotations', 'recommend', 'review', 'approve', 'issue', 'receive', 'invoices', 'pay', 'returns', 'refund', 'void', 'close', 'reports', 'policy'];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::PERMISSIONS as $a) {
                Permission::updateOrCreate(['slug' => 'procurement.'.$a], ['name' => 'Procurement — '.str($a)->headline(), 'module' => 'procurement', 'description' => 'Requires procurement view and linked parent access. Independent actors are required.']);
            }
            foreach (['procurement-officer' => 'Procurement Officer', 'receiving-officer' => 'Receiving Officer'] as $slug => $name) {
                Role::firstOrCreate(['slug' => $slug], ['name' => $name, 'status' => 'active', 'is_system' => true]);
            }
            $roles = ['procurement-officer' => ['view', 'manage', 'suppliers', 'quotations', 'issue', 'invoices'], 'receiving-officer' => ['view', 'receive'], 'project-officer' => ['view', 'manage'], 'implementation-committee' => ['view', 'manage'], 'chief-coordinator' => ['view', 'recommend'], 'general-secretary' => ['view', 'review'], 'decision-making-committee' => ['view', 'approve', 'close'], 'treasurer' => ['view', 'pay', 'returns', 'refund', 'reports'], 'auditor' => ['view', 'reports'], 'auditor-reviewer' => ['view', 'reports'], 'administrator' => self::PERMISSIONS];
            foreach ($roles as $slug => $actions) {
                $permissions = array_map(fn ($a) => 'procurement.'.$a, $actions);
                if (in_array($slug, ['procurement-officer', 'receiving-officer'])) {
                    $permissions = [...$permissions, 'projects.view', 'business.view'];
                }
                Role::where('slug', $slug)->first()?->permissions()->syncWithoutDetaching(Permission::whereIn('slug', $permissions)->pluck('id'));
            }
        });
    }
}
