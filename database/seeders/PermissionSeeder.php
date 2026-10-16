<?php

namespace Database\Seeders;

use App\Domain\Users\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // Members
            [
                'name' => 'View Members',
                'slug' => 'members.view',
                'module' => 'members',
            ],
            [
                'name' => 'Create Members',
                'slug' => 'members.create',
                'module' => 'members',
            ],
            [
                'name' => 'Update Members',
                'slug' => 'members.update',
                'module' => 'members',
            ],
            [
                'name' => 'Delete Members',
                'slug' => 'members.delete',
                'module' => 'members',
            ],

            // Committees
            [
                'name' => 'View Committees',
                'slug' => 'committees.view',
                'module' => 'committees',
            ],
            [
                'name' => 'Create Committees',
                'slug' => 'committees.create',
                'module' => 'committees',
            ],
            [
                'name' => 'Update Committees',
                'slug' => 'committees.update',
                'module' => 'committees',
            ],
            [
                'name' => 'Delete Committees',
                'slug' => 'committees.delete',
                'module' => 'committees',
            ],
            [
                'name' => 'Manage Committee Members',
                'slug' => 'committees.members.manage',
                'module' => 'committees',
            ],

            // Finance
            [
                'name' => 'View Finance',
                'slug' => 'finance.view',
                'module' => 'finance',
            ],
            [
                'name' => 'Manage Donations',
                'slug' => 'finance.donations.manage',
                'module' => 'finance',
            ],
            [
                'name' => 'Manage Expenses',
                'slug' => 'finance.expenses.manage',
                'module' => 'finance',
            ],
            [
                'name' => 'Manage Funds',
                'slug' => 'finance.funds.manage',
                'module' => 'finance',
            ],
            [
                'name' => 'Approve Expenses',
                'slug' => 'finance.expenses.approve',
                'module' => 'finance',
            ],
            [
                'name' => 'View Financial Reports',
                'slug' => 'finance.reports.view',
                'module' => 'finance',
            ],

            // Welfare
            [
                'name' => 'View Welfare',
                'slug' => 'welfare.view',
                'module' => 'welfare',
            ],
            [
                'name' => 'Manage Beneficiaries',
                'slug' => 'welfare.beneficiaries.manage',
                'module' => 'welfare',
            ],
            [
                'name' => 'Manage Assistance',
                'slug' => 'welfare.assistance.manage',
                'module' => 'welfare',
            ],

            // Qard-e-Hasana
            [
                'name' => 'View Qard-e-Hasana',
                'slug' => 'qard.view',
                'module' => 'qard',
            ],
            [
                'name' => 'Manage Qard-e-Hasana',
                'slug' => 'qard.manage',
                'module' => 'qard',
            ],

            // Small Business
            [
                'name' => 'View Small Business',
                'slug' => 'business.view',
                'module' => 'business',
            ],
            [
                'name' => 'Manage Small Business',
                'slug' => 'business.manage',
                'module' => 'business',
            ],

            // Projects
            [
                'name' => 'View Projects',
                'slug' => 'projects.view',
                'module' => 'projects',
            ],
            [
                'name' => 'Manage Projects',
                'slug' => 'projects.manage',
                'module' => 'projects',
            ],

            // Operations
            [
                'name' => 'View Tasks',
                'slug' => 'tasks.view',
                'module' => 'operations',
            ],
            [
                'name' => 'Manage Tasks',
                'slug' => 'tasks.manage',
                'module' => 'operations',
            ],
            [
                'name' => 'View Meetings',
                'slug' => 'meetings.view',
                'module' => 'operations',
            ],
            [
                'name' => 'Manage Meetings',
                'slug' => 'meetings.manage',
                'module' => 'operations',
            ],
            [
                'name' => 'Manage Procurement',
                'slug' => 'procurement.manage',
                'module' => 'operations',
            ],

            // Documents
            [
                'name' => 'View Documents',
                'slug' => 'documents.view',
                'module' => 'documents',
            ],
            [
                'name' => 'Upload Documents',
                'slug' => 'documents.upload',
                'module' => 'documents',
            ],
            [
                'name' => 'Delete Documents',
                'slug' => 'documents.delete',
                'module' => 'documents',
            ],

            // Reports
            [
                'name' => 'View Reports',
                'slug' => 'reports.view',
                'module' => 'reports',
            ],
            [
                'name' => 'Generate Reports',
                'slug' => 'reports.generate',
                'module' => 'reports',
            ],

            // Audit
            [
                'name' => 'View Audit Trail',
                'slug' => 'audit.view',
                'module' => 'audit',
            ],

            // Users
            [
                'name' => 'View Users',
                'slug' => 'users.view',
                'module' => 'users',
            ],
            [
                'name' => 'Manage Users',
                'slug' => 'users.manage',
                'module' => 'users',
            ],
            [
                'name' => 'Manage Roles',
                'slug' => 'roles.manage',
                'module' => 'users',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'slug' => $permission['slug'],
                ],
                $permission
            );
        }
    }
}