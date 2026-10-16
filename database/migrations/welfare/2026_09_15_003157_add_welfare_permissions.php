<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permissions = [
            [
                'name' => 'View Welfare',
                'slug' => 'welfare.view',
                'module' => 'welfare',
                'description' => 'View welfare records and dashboards.',
            ],
            [
                'name' => 'Manage Beneficiaries',
                'slug' => 'welfare.beneficiaries.manage',
                'module' => 'welfare',
                'description' => 'Create, edit and archive beneficiary records.',
            ],
            [
                'name' => 'Verify Beneficiaries',
                'slug' => 'welfare.beneficiaries.verify',
                'module' => 'welfare',
                'description' => 'Verify or reject beneficiary records.',
            ],
            [
                'name' => 'Approve Beneficiaries',
                'slug' => 'welfare.beneficiaries.approve',
                'module' => 'welfare',
                'description' => 'Approve or reject beneficiary eligibility.',
            ],
            [
                'name' => 'View Welfare Assistance',
                'slug' => 'welfare.assistance.view',
                'module' => 'welfare',
                'description' => 'View welfare assistance records.',
            ],
            [
                'name' => 'Manage Welfare Assistance',
                'slug' => 'welfare.assistance.manage',
                'module' => 'welfare',
                'description' => 'Create, edit and archive welfare assistance records.',
            ],
            [
                'name' => 'Approve Welfare Assistance',
                'slug' => 'welfare.assistance.approve',
                'module' => 'welfare',
                'description' => 'Approve or reject welfare assistance requests.',
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                [
                    'name' => $permission['name'],
                    'module' => $permission['module'],
                    'description' => $permission['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('slug', [
                'welfare.view',
                'welfare.beneficiaries.manage',
                'welfare.beneficiaries.verify',
                'welfare.beneficiaries.approve',
                'welfare.assistance.view',
                'welfare.assistance.manage',
                'welfare.assistance.approve',
            ])
            ->delete();
    }
};