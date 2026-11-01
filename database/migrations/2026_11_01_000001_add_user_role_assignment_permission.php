<?php

use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['slug' => 'users.assign-roles'], [
            'name' => 'Assign user roles', 'module' => 'users',
        ]);
        foreach (Role::where('slug', 'administrator')->get() as $role) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }

    public function down(): void
    {
        // Retain deliberately configured access grants and their audit history.
    }
};
