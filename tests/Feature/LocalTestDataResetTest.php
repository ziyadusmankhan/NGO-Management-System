<?php

namespace Tests\Feature;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use Database\Seeders\WelfareReviewRoleSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SQLite3;
use Tests\TestCase;

class LocalTestDataResetTest extends TestCase
{
    public function test_reset_preview_backup_and_admin_preservation_on_disposable_database(): void
    {
        $directory = sys_get_temp_dir().'/foundation-reset-test-'.Str::uuid();
        mkdir($directory, 0700);
        $path = $directory.'/database.sqlite';
        touch($path);
        $originalStorage = $this->app->storagePath();
        $originalDefault = config('database.default');
        $originalEnvironment = $this->app['env'];
        config(['database.connections.reset_test' => [
            'driver' => 'sqlite', 'database' => $path, 'prefix' => '', 'foreign_key_constraints' => true,
        ], 'database.default' => 'reset_test']);
        $this->app->useStoragePath($directory.'/storage');
        try {
            // Relax durability only for this disposable test database.
            DB::statement('PRAGMA journal_mode = WAL');
            DB::statement('PRAGMA synchronous = OFF');
            $this->assertSame(0, Artisan::call('migrate', ['--database' => 'reset_test', '--force' => true]));
            (new WelfareReviewRoleSeeder)->run();
            $role = Role::create(['name' => 'Administrator', 'slug' => 'administrator', 'status' => 'active']);
            $admin = User::create(['name' => 'Keep Admin', 'email' => 'keep@example.test', 'password' => 'unchanged-secret', 'status' => 'active']);
            $admin->roles()->attach($role);
            $other = User::create(['name' => 'Delete Admin', 'email' => 'remove@example.test', 'password' => 'other-secret', 'status' => 'active']);
            $other->roles()->attach($role);
            $fund = Fund::create(['fund_reference' => 'FND-OLD', 'name' => 'Old fund', 'purpose' => 'general_welfare',
                'status' => 'active', 'opening_balance' => '10.25']);
            DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $admin->id, 'payload' => '', 'last_activity' => time()]);
            $passwordHash = $admin->getRawOriginal('password');
            $permissions = DB::table('permissions')->count();
            $this->assertSame(1, Artisan::call('foundation:reset-test-data', ['--admin-email' => $admin->email, '--execute' => true]));
            $this->assertSame(2, User::count(), 'Testing environment must refuse destructive reset.');
            $this->app['env'] = 'local';
            $this->assertSame(1, Artisan::call('foundation:reset-test-data', ['--admin-email' => 'missing@example.test', '--execute' => true]));
            $this->assertSame(0, Artisan::call('foundation:reset-test-data', ['--admin-email' => $admin->email]));
            $this->assertSame(1, Fund::count(), 'Preview must not delete business data.');
            $this->assertSame(2, User::count());
            $this->assertSame(0, Artisan::call('foundation:reset-test-data', ['--admin-email' => $admin->email, '--execute' => true]));
            $this->assertSame(0, Fund::withTrashed()->count());
            $this->assertSame(1, User::withTrashed()->count());
            $this->assertSame($admin->id, User::sole()->id);
            $this->assertSame($passwordHash, User::sole()->getRawOriginal('password'));
            $this->assertTrue(User::sole()->hasRole('administrator'));
            $this->assertSame($permissions, DB::table('permissions')->count());
            $this->assertSame(0, DB::table('sessions')->count());
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
            $backups = glob($directory.'/storage/app/backups/*.sqlite');
            $this->assertCount(1, $backups);
            $backup = new SQLite3($backups[0], SQLITE3_OPEN_READONLY);
            $this->assertSame('ok', $backup->querySingle('PRAGMA integrity_check'));
            $this->assertSame(2, $backup->querySingle('SELECT COUNT(*) FROM users'));
            $this->assertSame(1, $backup->querySingle('SELECT COUNT(*) FROM funds'));
            $backup->close();
            $newFund = Fund::create(['fund_reference' => 'FND-00001', 'name' => 'New fund', 'purpose' => 'general_welfare',
                'status' => 'active', 'opening_balance' => '0.00']);
            $this->assertSame(1, $newFund->id);
        } finally {
            DB::purge('reset_test');
            config(['database.default' => $originalDefault]);
            $this->app['env'] = $originalEnvironment;
            $this->app->useStoragePath($originalStorage);
            // Only remove this test's known temporary files and empty directories.
            foreach (glob($directory.'/storage/app/backups/before-phase5-reset-*.sqlite*') ?: [] as $backupFile) {
                unlink($backupFile);
            }
            foreach ([$path, $path.'-wal', $path.'-shm', $path.'-journal'] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            foreach (['/storage/app/backups', '/storage/app', '/storage', ''] as $suffix) {
                if (is_dir($directory.$suffix)) {
                    rmdir($directory.$suffix);
                }
            }
        }
    }
}
