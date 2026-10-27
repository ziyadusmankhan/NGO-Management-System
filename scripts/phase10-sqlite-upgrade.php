<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// Local activation helper. Run backup BEFORE migration, then verify BEFORE browsing.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || DB::connection()->getDriverName() !== 'sqlite') {
    throw new RuntimeException('This helper requires the local SQLite environment.');
}
$live = DB::connection()->getDatabaseName();
$manifestPath = storage_path('app/backups/phase10-activation-manifest.json');
$quote = fn ($name) => '"'.str_replace('"', '""', $name).'"';
$snapshot = function (SQLite3 $db, array $prior = []) use ($quote): array {
    $result = [];
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
    while ($row = $tables->fetchArray(SQLITE3_ASSOC)) {
        $name = $row['name'];
        $columns = $prior[$name]['columns'] ?? [];
        if (! $columns) {
            $info = $db->query('PRAGMA table_info('.$quote($name).')');
            while ($column = $info->fetchArray(SQLITE3_ASSOC)) {
                $columns[] = $column['name'];
            }
        }
        $items = [];
        $query = $db->query('SELECT '.implode(',', array_map($quote, $columns)).' FROM '.$quote($name));
        while ($item = $query->fetchArray(SQLITE3_ASSOC)) {
            $items[] = json_encode($item, JSON_THROW_ON_ERROR);
        }
        sort($items);
        $result[$name] = ['columns' => $columns, 'count' => count($items), 'digest' => hash('sha256', implode("\n", $items))];
    }

    return $result;
};
$source = new SQLite3($live, SQLITE3_OPEN_READONLY);
$source->enableExceptions(true);
if ($source->querySingle('PRAGMA integrity_check') !== 'ok' || $source->query('PRAGMA foreign_key_check')->fetchArray()) {
    throw new RuntimeException('Database integrity or foreign-key check failed.');
}
if (($argv[1] ?? '') === 'backup') {
    if (file_exists($manifestPath)) {
        throw new RuntimeException('A Phase 10 backup manifest already exists. Keep and review it; do not overwrite it.');
    }
    $path = storage_path('app/backups/before-phase10-activation-'.now('Asia/Karachi')->format('Ymd-His').'.sqlite');
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0700, true);
    }
    $backup = new SQLite3($path, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    if (! $source->backup($backup) || $backup->querySingle('PRAGMA integrity_check') !== 'ok' || $backup->query('PRAGMA foreign_key_check')->fetchArray()) {
        throw new RuntimeException('Backup verification failed. Do not migrate.');
    }
    $before = $snapshot($backup);
    $backup->close();
    file_put_contents($manifestPath, json_encode(['backup' => $path, 'tables' => $before], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo json_encode(['backup' => $path, 'integrity' => 'ok', 'tables' => count($before), 'users' => $before['users']['count']], JSON_PRETTY_PRINT);
} elseif (($argv[1] ?? '') === 'verify') {
    $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
    $after = $snapshot($source, $manifest['tables']);
    $checked = 0;
    $activityTimestampChanged = false;
    $backup = new SQLite3($manifest['backup'], SQLITE3_OPEN_READONLY);
    $userIdentity = function (SQLite3 $db): array {
        $rows = [];
        $query = $db->query('SELECT * FROM users ORDER BY id');
        while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
            unset($row['last_active_at']);
            $rows[] = $row;
        }

        return $rows;
    };
    foreach ($manifest['tables'] as $table => $before) {
        if (in_array($table, ['migrations', 'roles', 'permissions', 'role_permissions', 'cache', 'cache_locks', 'sessions'])) {
            continue;
        }
        if ($before !== ($after[$table] ?? null)) {
            // Browsing may update last-active between backup and activation. Compare every
            // other user column directly against the verified backup, including password hashes.
            if ($table === 'users' && $userIdentity($source) === $userIdentity($backup)) {
                $activityTimestampChanged = true;
            } else {
                throw new RuntimeException('Unexpected existing data change: '.$table);
            }
        }
        $checked++;
    }
    $backup->close();
    $new = array_diff_key($after, $manifest['tables']);
    echo json_encode(['integrity' => 'ok', 'foreign_key_violations' => 0, 'unchanged_existing_tables' => $checked, 'new_tables' => array_keys($new), 'users' => $after['users']['count'], 'user_identity_and_passwords_unchanged' => true, 'last_active_changed_since_backup' => $activityTimestampChanged], JSON_PRETTY_PRINT);

} else {
    throw new RuntimeException('Use backup or verify.');
}
$source->close();
