<?php

namespace Database\Seeders;

use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Database\Seeder;

class OperationsReportingRoleSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            'tasks.view' => 'View tasks', 'tasks.manage' => 'Create, assign and manage tasks',
            'meetings.view' => 'View meetings', 'meetings.manage' => 'Schedule meetings and record minutes',
            'reports.view' => 'View eligible organizational reports', 'reports.generate' => 'Generate and export eligible reports',
        ];
        foreach ($definitions as $slug => $name) {
            Permission::updateOrCreate(['slug' => $slug], ['name' => $name, 'module' => str_starts_with($slug, 'reports.') ? 'reports' : 'operations']);
        }
        $map = [
            'administrator' => array_keys($definitions),
            'general-secretary' => array_keys($definitions),
            'committee-coordinator' => ['tasks.view', 'tasks.manage', 'meetings.view', 'meetings.manage', 'reports.view', 'reports.generate'],
            'project-officer' => ['tasks.view', 'tasks.manage', 'meetings.view', 'meetings.manage', 'reports.view', 'reports.generate'],
            'implementation-committee' => ['tasks.view', 'tasks.manage', 'meetings.view', 'meetings.manage', 'reports.view'],
            'treasurer' => ['tasks.view', 'meetings.view', 'reports.view', 'reports.generate'],
            'auditor' => ['tasks.view', 'meetings.view', 'reports.view', 'reports.generate'],
            'auditor-reviewer' => ['tasks.view', 'meetings.view', 'reports.view', 'reports.generate'],
            'records-officer' => ['tasks.view', 'meetings.view', 'reports.view', 'reports.generate'],
            'viewer' => ['tasks.view', 'meetings.view', 'reports.view'],
        ];
        foreach ($map as $role => $slugs) {
            Role::where('slug', $role)->first()?->permissions()->syncWithoutDetaching(Permission::whereIn('slug', $slugs)->pluck('id'));
        }
    }
}
