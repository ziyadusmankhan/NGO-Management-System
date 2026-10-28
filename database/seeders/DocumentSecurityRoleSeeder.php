<?php

namespace Database\Seeders;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentSecurityRoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $ids = [];
            foreach (['view', 'download', 'upload', 'manage', 'share', 'archive', 'restore'] as $action) {
                $ids[] = Permission::updateOrCreate(['slug' => 'documents.'.$action], ['name' => ucfirst($action).' eligible documents', 'module' => 'documents'])->id;
            }
            $recovery = Permission::updateOrCreate(['slug' => 'users.password-reset'], ['name' => 'Issue temporary account recovery passwords', 'module' => 'users']);
            $officer = Role::firstOrCreate(['slug' => 'records-officer'], ['name' => 'Records Officer', 'status' => 'active', 'is_system' => true]);
            $officer->permissions()->syncWithoutDetaching($ids);
            Role::where('slug', 'administrator')->first()?->permissions()->syncWithoutDetaching([...$ids, $recovery->id]);
            $read = Permission::whereIn('slug', ['documents.view', 'documents.download'])->pluck('id');
            foreach (Role::whereIn('slug', ['auditor-reviewer', 'auditor'])->get() as $auditor) {
                $auditor->permissions()->syncWithoutDetaching($read);
            }
            if (! AuditEvent::where('source_key', 'phase11:permissions:v1')->exists()) {
                $archive = Permission::whereIn('slug', ['documents.archive', 'documents.restore'])->pluck('id');
                $old = Permission::where('slug', 'documents.delete')->first();
                if ($old) {
                    foreach (Role::whereHas('permissions', fn ($q) => $q->where('slug', 'documents.delete'))->get() as $role) {
                        $role->permissions()->syncWithoutDetaching($archive);
                        $role->permissions()->detach($old);
                    }
                }
                app(AuditRecorder::class)->record('security', 'document_permissions_upgraded', ['source_key' => 'phase11:permissions:v1', 'actor_id' => null]);
            }
        });
    }
}
