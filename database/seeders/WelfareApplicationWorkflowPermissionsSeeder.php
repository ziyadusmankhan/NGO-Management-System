<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WelfareApplicationWorkflowPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = now();

            $definitions = [
                'welfare.applications.recommend' => [
                    'name' => 'Recommend Welfare Applications',
                    'description' => 'Submit implementation committee recommendations for welfare applications.',
                ],
                'welfare.applications.review' => [
                    'name' => 'Review Welfare Applications',
                    'description' => 'Review welfare application recommendations as General Secretary.',
                ],
                'welfare.applications.approve' => [
                    'name' => 'Approve Welfare Applications',
                    'description' => 'Make final decisions on welfare applications as the Decision-Making Committee.',
                ],
            ];

            $permissionIds = [];

            // Create the new permissions, or update them if they already exist.
            foreach ($definitions as $slug => $details) {
                $existingId = DB::table('permissions')
                    ->where('slug', $slug)
                    ->value('id');

                $data = [
                    'name' => $details['name'],
                    'slug' => $slug,
                    'module' => 'welfare',
                    'description' => $details['description'],
                    'updated_at' => $now,
                ];

                if ($existingId) {
                    DB::table('permissions')
                        ->where('id', $existingId)
                        ->update($data);

                    $permissionIds[$slug] = (int) $existingId;
                } else {
                    $permissionIds[$slug] = (int) DB::table('permissions')
                        ->insertGetId($data + [
                            'created_at' => $now,
                        ]);
                }
            }

            // Reuse the existing application-view permission.
            $viewPermissionId = DB::table('permissions')
                ->where('slug', 'welfare.assistance.view')
                ->value('id');

            if (!$viewPermissionId) {
                throw new RuntimeException(
                    'The existing welfare.assistance.view permission was not found.'
                );
            }

            $permissionIds['welfare.assistance.view'] =
                (int) $viewPermissionId;

            // The Administrator role must already exist.
            $admin = DB::table('roles')
                ->where('name', 'Administrator')
                ->first();

            if (!$admin) {
                throw new RuntimeException(
                    'The Administrator role was not found.'
                );
            }

            // Find or create the dedicated final-decision role.
            $committeeRole = DB::table('roles')
                ->where(function ($query) {
                    $query->where(
                        'name',
                        'Decision-Making Committee'
                    )->orWhere(
                        'slug',
                        'decision-making-committee'
                    );
                })
                ->first();

            if (!$committeeRole) {
                $committeeRoleId = (int) DB::table('roles')
                    ->insertGetId([
                        'name' => 'Decision-Making Committee',
                        'slug' => 'decision-making-committee',
                        'description' =>
                            'Reviews and makes final decisions on eligible welfare applications.',
                        'is_system' => 0,
                        'status' => $admin->status ?: 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
            } else {
                $committeeRoleId = (int) $committeeRole->id;

                DB::table('roles')
                    ->where('id', $committeeRoleId)
                    ->update([
                        'name' => 'Decision-Making Committee',
                        'slug' => 'decision-making-committee',
                        'description' =>
                            'Reviews and makes final decisions on eligible welfare applications.',
                        'updated_at' => $now,
                    ]);
            }

            $roleAssignments = [
                'Administrator' => [
                    'welfare.applications.recommend',
                    'welfare.applications.review',
                    'welfare.applications.approve',
                    'welfare.assistance.view',
                ],

                'Committee Coordinator' => [
                    'welfare.applications.recommend',
                    'welfare.assistance.view',
                ],

                'General Secretary' => [
                    'welfare.applications.review',
                    'welfare.assistance.view',
                ],

                'Decision-Making Committee' => [
                    'welfare.applications.approve',
                    'welfare.assistance.view',
                ],
            ];

            foreach ($roleAssignments as $roleName => $slugs) {
                $roleId = $roleName === 'Decision-Making Committee'
                    ? $committeeRoleId
                    : DB::table('roles')
                        ->where('name', $roleName)
                        ->value('id');

                if (!$roleId) {
                    throw new RuntimeException(
                        "The required role '{$roleName}' was not found."
                    );
                }

                foreach ($slugs as $slug) {
                    $permissionId = $permissionIds[$slug];

                    $exists = DB::table('role_permissions')
                        ->where('role_id', $roleId)
                        ->where('permission_id', $permissionId)
                        ->exists();

                    if (!$exists) {
                        DB::table('role_permissions')->insert([
                            'role_id' => $roleId,
                            'permission_id' => $permissionId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        });
    }
}