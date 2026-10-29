<?php

use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Security\Services\FieldEncryption;
use App\Domain\Security\Services\SensitiveFields;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Local-only activation, with a verified database/file snapshot and preservation checks.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! app()->environment('local') || DB::connection()->getDriverName() !== 'sqlite' || ! app()->isDownForMaintenance()) {
    throw new RuntimeException('Activation requires the local SQLite application in maintenance mode.');
}
$backup = realpath($argv[1] ?? '');
$base = realpath(storage_path('app/backups'));
if (! $backup || ! $base || ! str_starts_with($backup, $base.DIRECTORY_SEPARATOR) || ! is_file($backup.'/manifest.json')) {
    throw new RuntimeException('A verified Phase 11 backup is required.');
}
$m = json_decode(file_get_contents($backup.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
if (hash_file('sha256', $backup.'/database.sqlite') !== $m['database_sha256']) {
    throw new RuntimeException('Database backup checksum failed.');
}
foreach ($m['files'] as $path => $hash) {
    if (str_contains($path, '..') || str_starts_with($path, '/') || str_contains($path, ':')) {
        throw new RuntimeException('Unsafe backup path.');
    }
    if (hash_file('sha256', $backup.'/uploads/'.$path) !== $hash || ! is_file(storage_path('app/'.$path)) || hash_file('sha256', storage_path('app/'.$path)) !== $hash) {
        throw new RuntimeException('Backup/live upload mismatch. Take a current backup first.');
    }
}
$source = new SQLite3($backup.'/database.sqlite', SQLITE3_OPEN_READONLY);
$tables = [];
$rows = $source->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
while ($r = $rows->fetchArray(SQLITE3_ASSOC)) {
    $tables[] = $r['name'];
}
$excluded = ['migrations', 'permissions', 'roles', 'role_permissions', 'user_roles', 'audit_events', 'sessions', 'cache', 'cache_locks'];
// Before touching live schema, compare all retained source rows to the snapshot.
foreach ($tables as $table) {
    if (in_array($table, $excluded, true)) {
        continue;
    }
    $before = [];
    $rows = $source->query('SELECT * FROM "'.$table.'"');
    while ($r = $rows->fetchArray(SQLITE3_ASSOC)) {
        $before[] = json_encode($r);
    }
    $current = DB::table($table)->get()->map(fn ($r) => json_encode((array) $r))->all();
    sort($before);
    sort($current);
    if ($before !== $current) {
        throw new RuntimeException('Live records changed since backup in '.$table.'. Create a fresh backup.');
    }
}
if (! copy(base_path('.env'), $backup.'/environment.recovery')) {
    throw new RuntimeException('Could not preserve matching recovery keys.');
}
DB::statement('PRAGMA secure_delete = ON');
$results = [];
foreach ([['migrate', ['--force' => true]], ['db:seed', ['--class' => 'DocumentSecurityRoleSeeder', '--force' => true]], ['documents:import', ['--dry-run' => true]], ['documents:import', []], ['security:encrypt', ['--dry-run' => true]], ['security:encrypt', []], ['security:encrypt', ['--verify' => true]], ['security:health', []], ['audit:health', []]] as [$command,$args]) {
    $exit = Artisan::call($command, $args);
    $results[] = ['command' => $command, 'options' => $args, 'exit' => $exit];
    if ($exit !== 0) {
        throw new RuntimeException('Activation stopped at '.$command.'. Keep maintenance mode active and inspect the sanitized command output.');
    }
}
$checked = 0;
foreach ($tables as $table) {
    if (in_array($table, $excluded, true)) {
        continue;
    }
    $columns = $source->query('PRAGMA table_info("'.$table.'")');
    $hasId = false;
    while ($column = $columns->fetchArray(SQLITE3_ASSOC)) {
        if ($column['name'] === 'id') {
            $hasId = true;
        }
    }
    if (DB::table($table)->count() != $source->querySingle('SELECT COUNT(*) FROM "'.$table.'"')) {
        throw new RuntimeException('Retained row count changed in '.$table);
    }
    if (! $hasId) {
        continue;
    }
    $rows = $source->query('SELECT * FROM "'.$table.'"');
    while ($before = $rows->fetchArray(SQLITE3_ASSOC)) {
        $after = (array) DB::table($table)->where('id', $before['id'])->first();
        foreach ($before as $field => $value) {
            $actual = in_array($field, SensitiveFields::fields($table), true) ? app(FieldEncryption::class)->decode($after[$field]) : $after[$field];
            if ($actual !== $value) {
                throw new RuntimeException('Preservation check failed for '.$table.' #'.$before['id'].' field '.$field);
            }
            $checked++;
        }
    }
}
DB::transaction(function () {
    DB::table('users')->update(['session_version' => DB::raw('session_version + 1'), 'remember_token' => null]);
    DB::table('sessions')->delete();
    app(AuditRecorder::class)->record('security', 'phase11_activated', ['actor_kind' => 'system', 'summary' => 'Documents, encrypted fields and session policy activated after verified backup and preservation checks.']);
});
$source->close();
DB::select('PRAGMA wal_checkpoint(TRUNCATE)');
DB::statement('VACUUM');
DB::select('PRAGMA wal_checkpoint(TRUNCATE)');
if (DB::selectOne('PRAGMA integrity_check')->integrity_check !== 'ok' || DB::select('PRAGMA foreign_key_check')) {
    throw new RuntimeException('Post-conversion integrity check failed.');
}
$report = ['activated_at' => now('Asia/Karachi')->toIso8601String(), 'backup' => $backup, 'commands' => $results, 'original_values_verified' => $checked, 'files_verified' => count($m['files']), 'users' => DB::table('users')->count(), 'documents' => DB::table('library_documents')->count(), 'missing_versions' => DB::table('library_document_versions')->where('status', 'missing')->count(), 'database_integrity' => 'ok', 'original_password_hashes' => 'unchanged', 'old_sessions' => 'revoked', 'sqlite_free_pages' => DB::selectOne('PRAGMA freelist_count')->freelist_count, 'pre_upgrade_backup_contains_plaintext' => true];
file_put_contents($backup.'/activation.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
