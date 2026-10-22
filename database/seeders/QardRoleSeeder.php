<?php

namespace Database\Seeders;

use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QardRoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (['view' => 'View Qard', 'manage' => 'Manage Qard Applications', 'verify' => 'Verify Qard Applications',
                'recommend' => 'Recommend Qard', 'review' => 'Review Qard as General Secretary', 'approve' => 'Make Qard Final Decision',
                'agreements' => 'Manage Qard Agreements', 'disburse' => 'Disburse Qard Principal', 'repay' => 'Record Qard Repayments',
                'followups' => 'Manage Qard Follow-ups', 'void' => 'Void Qard Financial Movements'] as $slug => $name) {
                Permission::updateOrCreate(['slug' => 'qard.'.$slug], ['name' => $name, 'module' => 'qard', 'description' => 'Requires View Qard. Approval and financial actions are separately authorized.']);
            }
            Role::firstOrCreate(['slug' => 'qard-officer'], ['name' => 'Qard Officer', 'description' => 'Manages loan applications, verification, agreements and follow-ups.', 'status' => 'active', 'is_system' => true]);
            $assignments = [
                'qard-officer' => ['view', 'manage', 'verify', 'agreements', 'followups'],
                'implementation-committee' => ['view', 'recommend'],
                'general-secretary' => ['view', 'review'],
                'decision-making-committee' => ['view', 'approve'],
                'treasurer' => ['view', 'disburse', 'repay'],
                'administrator' => ['view', 'manage', 'verify', 'recommend', 'review', 'approve', 'agreements', 'disburse', 'repay', 'followups', 'void'],
            ];
            foreach ($assignments as $role => $slugs) {
                Role::where('slug', $role)->first()?->permissions()->syncWithoutDetaching(
                    Permission::whereIn('slug', array_map(fn ($s) => 'qard.'.$s, $slugs))->pluck('id'));
            }
        });
    }
}
