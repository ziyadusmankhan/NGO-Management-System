<?php

namespace Database\Seeders;

use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrator',
                'slug' => 'administrator',
                'description' => 'Full administrative access to the system.',
            ],

            [
                'name' => 'General Secretary',
                'slug' => 'general-secretary',
                'description' => 'Organization-wide administrative and coordination access.',
            ],

            [
                'name' => 'Treasurer',
                'slug' => 'treasurer',
                'description' => 'Financial management and financial reporting access.',
            ],

            [
                'name' => 'Committee Coordinator',
                'slug' => 'committee-coordinator',
                'description' => 'Committee and operational coordination access.',
            ],

            [
                'name' => 'Welfare Officer',
                'slug' => 'welfare-officer',
                'description' => 'Welfare and beneficiary management access.',
            ],

            [
                'name' => 'Project Officer',
                'slug' => 'project-officer',
                'description' => 'Project and field-operation management access.',
            ],

            [
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access to permitted records and reports.',
            ],
        ];

        foreach ($roles as $roleData) {

            $role = Role::updateOrCreate(
                [
                    'slug' => $roleData['slug'],
                ],
                [
                    ...$roleData,
                    'is_system' => true,
                    'status' => 'active',
                ]
            );

            $permissionMap = [

                'administrator' => Permission::pluck(
                    'id'
                )->all(),

                'general-secretary' => Permission::whereIn(
                    'slug',
                    [
                        'members.view',
                        'members.create',
                        'members.update',

                        'committees.view',
                        'committees.create',
                        'committees.update',
                        'committees.members.manage',

                        'projects.view',
                        'projects.manage',

                        'tasks.view',
                        'tasks.manage',

                        'meetings.view',
                        'meetings.manage',

                        'documents.view',
                        'documents.upload',

                        'reports.view',
                        'reports.generate',

                    ]
                )->pluck('id')->all(),

                'treasurer' => Permission::whereIn(
                    'slug',
                    [
                        'finance.view',
                        'finance.donations.manage',
                        'finance.expenses.manage',
                        'finance.expenses.approve',
                        'finance.funds.manage',
                        'finance.reports.view',
                        'reports.view',
                        'reports.generate',
                    ]
                )->pluck('id')->all(),

                'committee-coordinator' => Permission::whereIn(
                    'slug',
                    [
                        'members.view',

                        'committees.view',
                        'committees.members.manage',

                        'projects.view',
                        'projects.manage',

                        'tasks.view',
                        'tasks.manage',

                        'meetings.view',
                        'meetings.manage',

                        'documents.view',
                    ]
                )->pluck('id')->all(),

                'welfare-officer' => Permission::whereIn(
                    'slug',
                    [
                        'members.view',

                        'welfare.view',
                        'welfare.beneficiaries.manage',
                        'welfare.assistance.manage',

                        'qard.view',
                        'qard.manage',

                        'business.view',
                        'business.manage',

                        'documents.view',

                        'reports.view',
                    ]
                )->pluck('id')->all(),

                'project-officer' => Permission::whereIn(
                    'slug',
                    [
                        'members.view',
                        'committees.view',

                        'projects.view',
                        'projects.manage',

                        'tasks.view',
                        'tasks.manage',

                        'meetings.view',
                        'meetings.manage',

                        'procurement.manage',

                        'documents.view',

                        'reports.view',
                    ]
                )->pluck('id')->all(),

                'viewer' => Permission::whereIn(
                    'slug',
                    [
                        'members.view',
                        'committees.view',
                        'finance.view',
                        'welfare.view',
                        'qard.view',
                        'business.view',
                        'projects.view',
                        'tasks.view',
                        'meetings.view',
                        'documents.view',
                        'reports.view',
                    ]
                )->pluck('id')->all(),

            ];

            $role->permissions()->sync(
                $permissionMap[$role->slug] ?? []
            );
        }
    }
}
