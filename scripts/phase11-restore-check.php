<?php

use App\Domain\Security\Services\FieldEncryption;
use App\Domain\Security\Services\SensitiveFields;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Read-only with respect to the source database/uploads. All migrations and conversion run in a disposable restored copy.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$backup = realpath($argv[1] ?? '');
$base = realpath(storage_path('app/backups'));
if (! $backup || ! $base || ! str_starts_with($backup, $base.DIRECTORY_SEPARATOR) || ! is_file($backup.'/manifest.json')) {
    throw new RuntimeException('Provide an existing Phase 11 backup under private backups.');
}
$manifest = json_decode(file_get_contents($backup.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
if (hash_file('sha256', $backup.'/database.sqlite') !== $manifest['database_sha256']) {
    throw new RuntimeException('Backup database checksum failed.');
}
$target = storage_path('app/phase11-restore-'.Str::uuid());
mkdir($target, 0700, true);
copy($backup.'/database.sqlite', $target.'/database.sqlite');
foreach ($manifest['files'] as $path => $hash) {
    if (str_contains($path, '..') || str_starts_with($path, '/') || str_contains($path, ':')) {
        throw new RuntimeException('Unsafe backup manifest.');
    }
    $source = $backup.'/uploads/'.$path;
    if (! is_file($source) || hash_file('sha256', $source) !== $hash) {
        throw new RuntimeException('Backup file checksum failed.');
    }
    $dest = $target.'/uploads/'.$path;
    if (! is_dir(dirname($dest))) {
        mkdir(dirname($dest), 0700, true);
    }copy($source, $dest);
    if (hash_file('sha256', $dest) !== $hash) {
        throw new RuntimeException('Restored file checksum failed.');
    }
}
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $target.'/database.sqlite', 'filesystems.disks.private.root' => $target.'/uploads/private', 'filesystems.disks.local.root' => $target.'/uploads/private', 'filesystems.disks.public.root' => $target.'/uploads/public', 'cache.default' => 'array', 'session.driver' => 'array']);
DB::purge('sqlite');
foreach (['private', 'local', 'public'] as $disk) {
    Storage::forgetDisk($disk);
}
$original = new SQLite3($backup.'/database.sqlite', SQLITE3_OPEN_READONLY);
$originalTables = [];
$r = $original->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
while ($row = $r->fetchArray(SQLITE3_ASSOC)) {
    $originalTables[] = $row['name'];
}
$hashes = DB::table('users')->orderBy('id')->pluck('password', 'id')->all();
foreach ([['migrate', ['--force' => true]], ['db:seed', ['--class' => 'DocumentSecurityRoleSeeder', '--force' => true]], ['documents:import', ['--dry-run' => true]], ['documents:import', []], ['security:encrypt', ['--dry-run' => true]], ['security:encrypt', []], ['security:encrypt', ['--verify' => true]], ['documents:import', []]] as [$command,$args]) {
    if (Artisan::call($command, $args) !== 0) {
        throw new RuntimeException('Isolated rollout failed at '.$command);
    }
}
if ($hashes !== DB::table('users')->orderBy('id')->pluck('password', 'id')->all()) {
    throw new RuntimeException('Password preservation check failed.');
}
$checked = 0;
foreach (SensitiveFields::TABLES as $table => $fields) {
    $rows = $original->query('SELECT * FROM "'.$table.'"');
    while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
        $current = (array) DB::table($table)->where('id', $row['id'])->first();
        foreach ($fields as $field) {
            $decoded = app(FieldEncryption::class)->decode($current[$field]);
            // JSON histories remain their original serialized string during conversion.
            if ($decoded !== $row[$field]) {
                throw new RuntimeException('Protected field preservation failed for '.$table.' #'.$row['id'].' '.$field);
            }
            $checked++;
        }
    }
}
$excluded = ['migrations', 'permissions', 'roles', 'role_permissions', 'user_roles', 'audit_events'];
foreach ($originalTables as $table) {
    if (in_array($table, $excluded, true)) {
        continue;
    }
    $count = $original->querySingle('SELECT COUNT(*) FROM "'.$table.'"');
    if (DB::table($table)->count() != $count) {
        throw new RuntimeException('Retained row count mismatch in '.$table);
    }
}
if (DB::selectOne('PRAGMA integrity_check')->integrity_check !== 'ok' || DB::select('PRAGMA foreign_key_check')) {
    throw new RuntimeException('Restored database integrity check failed.');
}
$report = ['restore_directory' => $target, 'source_backup' => $backup, 'original_files_verified' => count($manifest['files']), 'protected_values_verified' => $checked, 'users_preserved' => count($hashes), 'document_count' => DB::table('library_documents')->count(), 'missing_versions' => DB::table('library_document_versions')->where('status', 'missing')->count(), 'integrity' => 'ok', 'password_hashes' => 'unchanged'];
file_put_contents($target.'/verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
