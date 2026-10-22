<?php

namespace Database\Seeders;

use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WelfareReviewRoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach ([
                'welfare.reviews.view' => 'View Reviews & Follow-ups',
                'welfare.reviews.manage' => 'Record Reviews & Follow-ups',
                'welfare.reviews.close' => 'Confirm Assistance Closure',
            ] as $slug => $name) {
                Permission::updateOrCreate(['slug' => $slug], [
                    'name' => $name, 'module' => 'reviews_followups',
                    'description' => 'Requires View Welfare Assistance; recording and closure also require View Reviews & Follow-ups.',
                ]);
            }
            Permission::firstOrCreate(['slug' => 'welfare.assistance.view'], [
                'name' => 'View Welfare Assistance', 'module' => 'welfare',
            ]);
            Permission::firstOrCreate(['slug' => 'welfare.view'], [
                'name' => 'View Welfare', 'module' => 'welfare',
            ]);
            $role = Role::firstOrCreate(['slug' => 'welfare-review-officer'], [
                'name' => 'Reviews & Follow-ups Officer',
                'description' => 'Reads assistance records and records reviews and follow-ups. Closure requires a separately authorized approver.',
                'status' => 'active', 'is_system' => true,
            ]);
            $role->permissions()->syncWithoutDetaching(Permission::whereIn('slug', [
                'welfare.view', 'welfare.assistance.view', 'welfare.reviews.view', 'welfare.reviews.manage',
            ])->pluck('id'));
            // Administrators already have an authorization bypass; also show the
            // new permissions checked in the role editor.
            Role::where('slug', 'administrator')->first()?->permissions()
                ->syncWithoutDetaching(Permission::where('module', 'reviews_followups')->pluck('id'));
        });
    }
}
