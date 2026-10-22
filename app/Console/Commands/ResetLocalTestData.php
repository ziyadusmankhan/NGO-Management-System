<?php

namespace App\Console\Commands;

use App\Domain\Users\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use SQLite3;

class ResetLocalTestData extends Command
{
    protected $signature = 'foundation:reset-test-data {--admin-email= : Exact email of the administrator to retain} {--execute : Perform the reset; otherwise preview only}';

    protected $description = 'Back up and clear local SQLite business data, retaining one existing administrator and role configuration.';

    public function handle(): int
    {
        if (! app()->environment('local') || DB::connection()->getDriverName() !== 'sqlite') {
            $this->error('This command only supports the local environment with SQLite.');
            return self::FAILURE;
        }
        $admin = User::where('email', $this->option('admin-email'))
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->where('slug', 'administrator'))->first();
        if (! $admin) {
            $this->error('Specify the exact email of an existing active administrator.');
            return self::FAILURE;
        }
        $keep = ['migrations', 'roles', 'permissions', 'role_permissions', 'users', 'user_roles'];
        $tables = array_column(DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"), 'name');
        $clear = array_values(array_diff($tables, $keep));
        $this->info("Retain: {$admin->email} (ID {$admin->id}), its existing password, roles, permissions and migrations.");
        $this->line('Clear: '.implode(', ', $clear).'; all other users and their role assignments.');
        if (! $this->option('execute')) {
            $this->info('Preview only. No data changed.');
            return self::SUCCESS;
        }
        $path = DB::connection()->getDatabaseName();
        if (! is_file($path) || ! class_exists(SQLite3::class)) {
            $this->error('A file-backed SQLite database and the SQLite3 extension are required for a verified backup.');
            return self::FAILURE;
        }
        $directory = storage_path('app/backups');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Cannot create the backup directory.');
        }
        $backupPath = $directory.'/before-phase5-reset-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(8)).'.sqlite';
        DB::transaction(function () use ($admin, $clear, $path, $backupPath) {
            // Acquire SQLite's writer lock before taking the online backup.
            // The no-op does not change the retained account.
            DB::update('UPDATE users SET id = id WHERE id = ?', [$admin->id]);
            $source = new SQLite3($path, SQLITE3_OPEN_READONLY);
            $backup = new SQLite3($backupPath, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            try {
                if (! $source->backup($backup) || $backup->querySingle('PRAGMA integrity_check') !== 'ok') {
                    throw new RuntimeException('Backup verification failed; reset cancelled.');
                }
                $userCount = (int) $backup->querySingle('SELECT COUNT(*) FROM users');
                if ($userCount !== DB::table('users')->count()) {
                    throw new RuntimeException('Backup record count did not match; reset cancelled.');
                }
            } finally {
                $backup->close();
                $source->close();
            }
            DB::statement('PRAGMA defer_foreign_keys = ON');
            $grammar = DB::connection()->getQueryGrammar();
            foreach ($clear as $table) {
                DB::statement('DELETE FROM '.$grammar->wrapTable($table));
                DB::delete('DELETE FROM sqlite_sequence WHERE name = ?', [$table]);
            }
            DB::table('user_roles')->where('user_id', '!=', $admin->id)->delete();
            DB::table('users')->where('id', '!=', $admin->id)->delete();
            DB::table('users')->where('id', $admin->id)->update(['remember_token' => null]);
            DB::table('sqlite_sequence')->where('name', 'users')->update(['seq' => $admin->id]);
            if (DB::select('PRAGMA foreign_key_check')) {
                throw new RuntimeException('Foreign key verification failed; reset rolled back.');
            }
            foreach ($clear as $table) {
                if (DB::table($table)->count() !== 0) {
                    throw new RuntimeException("Table {$table} is not empty; reset rolled back.");
                }
            }
            if (DB::table('users')->count() !== 1 ||
                DB::table('users')->where('id', $admin->id)->value('password') !== $admin->getRawOriginal('password')) {
                throw new RuntimeException('Administrator preservation failed; reset rolled back.');
            }
        });
        $this->info('Reset complete. One administrator remains; all business tables are empty.');
        $this->line('Verified backup: '.$backupPath);
        $this->line('All sessions were cleared. Sign in again with the retained account and its existing password.');
        return self::SUCCESS;
    }
}
