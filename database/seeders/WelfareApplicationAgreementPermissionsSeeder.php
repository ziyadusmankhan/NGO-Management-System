<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WelfareApplicationAgreementPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $permissions = [
            [
                'name' => 'View Welfare Agreements',
                'slug' => 'welfare.agreements.view',
                'module' => 'Welfare',
                'description' => 'View welfare agreement details and signature status.',
            ],
            [
                'name' => 'Manage Welfare Agreements',
                'slug' => 'welfare.agreements.manage',
                'module' => 'Welfare',
                'description' => 'Create and prepare agreements for approved welfare applications and record agreement signatures.',
            ],
        ];

        foreach ($permissions as $permission) {
            $permissionId = DB::table('permissions')
                ->where('slug', $permission['slug'])
                ->value('id');

            if ($permissionId) {
                DB::table('permissions')
                    ->where('id', $permissionId)
                    ->update([
                        'name' => $permission['name'],
                        'module' => $permission['module'],
                        'description' => $permission['description'],
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('permissions')->insert([
                    ...$permission,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $rolesByPermission = [
            'welfare.agreements.view' => [
                'Administrator',
                'General Secretary',
                'Treasurer',
                'Welfare Officer',
                'Decision-Making Committee',
            ],

            'welfare.agreements.manage' => [
                'Administrator',
                'General Secretary',
                'Welfare Officer',
            ],
        ];

        foreach ($rolesByPermission as $slug => $roleNames) {
            $permissionId = DB::table('permissions')
                ->where('slug', $slug)
                ->value('id');

            foreach ($roleNames as $roleName) {
                $roleId = DB::table('roles')
                    ->where('name', $roleName)
                    ->value('id');

                if (!$roleId) {
                    throw new \RuntimeException(
                        "Required role not found: {$roleName}"
                    );
                }

                $alreadyAssigned = DB::table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (!$alreadyAssigned) {
                    DB::table('role_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
}