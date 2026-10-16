<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * The "finance.funds.manage" permission slug is used by
 * routes/web.php (finance/funds routes) and by the fund Blade
 * views, but it was never added to PermissionSeeder. As a
 * result the permission row never existed, so no role -
 * including Administrator - could ever have it, and the
 * "Create Fund" / "Edit Fund" / "Delete Fund" actions were
 * unreachable for everyone.
 *
 * This migration backfills the permission and attaches it to
 * the Administrator and Treasurer roles so existing, already
 * seeded databases are fixed without needing a full db:seed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['slug' => 'finance.funds.manage'],
            [
                'name' => 'Manage Funds',
                'module' => 'finance',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $permissionId = DB::table('permissions')
            ->where('slug', 'finance.funds.manage')
            ->value('id');

        $roleIds = DB::table('roles')
            ->whereIn('slug', ['administrator', 'treasurer'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->updateOrInsert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('slug', 'finance.funds.manage')
            ->value('id');

        if ($permissionId) {
            DB::table('role_permissions')
                ->where('permission_id', $permissionId)
                ->delete();
        }

        DB::table('permissions')
            ->where('slug', 'finance.funds.manage')
            ->delete();
    }
};
