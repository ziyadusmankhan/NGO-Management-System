<?php

namespace Database\Seeders;

use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImplementationCommitteeRoleSeeder extends Seeder
{
    public function run(): void
    {
        $result = DB::transaction(function () {
            $definitions = [
                'welfare.view' => 'View Welfare',
                'welfare.assistance.view' => 'View Welfare Assistance',
                'welfare.applications.recommend' => 'Recommend Welfare Applications',
            ];
            $permissionIds = [];
            foreach ($definitions as $slug => $name) {
                $permissionIds[] = Permission::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'module' => 'welfare']
                )->id;
            }

            $role = Role::firstOrCreate(['slug' => 'implementation-committee'], [
                'name' => 'Implementation / Administration Committee',
                'description' => 'Amal Daramad Committee: reviews completed field verification and recommends welfare applications to the General Secretary.',
                'status' => 'active',
                'is_system' => true,
            ]);
            $created = $role->wasRecentlyCreated;
            $changes = $role->permissions()->syncWithoutDetaching($permissionIds);

            return ['role_id' => $role->id, 'created' => $created, 'attached_permission_ids' => $changes['attached']];
        });

        if ($result['created'] || $result['attached_permission_ids']) {
            Log::notice('Implementation committee role provisioned by database seeder.', $result + [
                'actor' => 'system:ImplementationCommitteeRoleSeeder',
            ]);
        }
    }
}
