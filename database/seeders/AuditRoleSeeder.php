<?php

namespace Database\Seeders;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuditRoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (['view' => 'View sanitized organization audit history', 'export' => 'Export sanitized audit history'] as $slug => $name) {
                Permission::updateOrCreate(['slug' => 'audit.'.$slug], ['name' => $name, 'module' => 'audit', 'description' => 'Does not grant source document access.']);
            }
            $ids = Permission::whereIn('slug', ['audit.view', 'audit.export'])->pluck('id');
            Role::firstOrCreate(['slug' => 'auditor-reviewer'], ['name' => 'Auditor / Reviewer', 'status' => 'active', 'is_system' => true]);
            foreach (['administrator', 'auditor-reviewer', 'auditor'] as $slug) {
                Role::where('slug', $slug)->first()?->permissions()->syncWithoutDetaching($ids);
            }
            if (! AuditEvent::where('source_key', 'phase10:permissions:v1')->exists()) {
                $before = [];
                foreach (['viewer', 'general-secretary'] as $slug) {
                    if ($role = Role::where('slug', $slug)->first()) {
                        $before[$slug] = $role->permissions()->whereIn('slug', ['audit.view', 'audit.export'])->pluck('slug')->all();
                        $role->permissions()->detach($ids);
                    }
                }
                app(AuditRecorder::class)->record('audit', 'default_permissions_upgraded', ['source_key' => 'phase10:permissions:v1', 'actor_id' => null, 'actor_kind' => 'system', 'summary' => 'Removed legacy Viewer and General Secretary audit defaults; Administrator and Auditor / Reviewer receive view and export.', 'before' => ['roles' => array_keys($before)], 'after' => ['roles' => ['administrator', 'auditor-reviewer']]]);
            }
        });
    }
}
