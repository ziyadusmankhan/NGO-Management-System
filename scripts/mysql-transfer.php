<?php

use Brick\Math\BigDecimal;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Local, snapshot-based SQLite -> MySQL transfer. Never writes to the source DB.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$mode = $argv[1] ?? '--check';
if (! app()->environment('local') || ! in_array($mode, ['--check', '--rehearse', '--transfer'])) {
    throw new RuntimeException('Local transfer modes: --check, --rehearse, --transfer.');
}
$database = (string) env('MYSQL_MIGRATION_DATABASE', 'foundation_management');
if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,39}$/', $database)) {
    throw new RuntimeException('Use a simple target database name, at most 40 characters.');
}
$mysql = config('database.connections.mysql');
$mysql = array_replace($mysql, ['url' => null, 'host' => env('MYSQL_MIGRATION_HOST', '127.0.0.1'), 'port' => env('MYSQL_MIGRATION_PORT', '3306'), 'username' => env('MYSQL_MIGRATION_USERNAME', 'root'), 'password' => env('MYSQL_MIGRATION_PASSWORD', ''), 'database' => 'information_schema']);
config(['database.connections.transfer_server' => $mysql]);
try {
    $server = DB::connection('transfer_server');
    $version = $server->selectOne('SELECT VERSION() AS version')->version;
} catch (Throwable $e) {
    fwrite(STDERR, 'MySQL connection failed ('.get_class($e).', SQLSTATE '.$e->getCode().', driver '.($e->errorInfo[1] ?? 'unavailable').'). Check local migration credentials and server status.'.PHP_EOL);
    exit(1);
}
$existing = $server->selectOne('SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = ?', [$database])->n;
echo json_encode(['server_version' => $version, 'target' => $database, 'existing_target_tables' => (int) $existing]).PHP_EOL;
if ($mode === '--check') {
    exit(0);
}
if ($mode === '--transfer' && ! app()->isDownForMaintenance()) {
    throw new RuntimeException('Enter maintenance mode before the final transfer.');
}
$backup = $argv[2] ?? '';
$source = realpath($backup.'/database.sqlite');
$backups = realpath(storage_path('app/backups'));
if (! $source || ! $backups || ! str_starts_with($source, $backups.DIRECTORY_SEPARATOR)) {
    throw new RuntimeException('Provide a verified backup directory inside storage/app/backups.');
}
$manifest = json_decode(file_get_contents($backup.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
if (hash_file('sha256', $source) !== $manifest['database_sha256']) {
    throw new RuntimeException('Backup checksum mismatch.');
}
if ($mode === '--transfer' && $existing) {
    throw new RuntimeException('Target contains tables; refusing to overwrite them.');
}
if ($mode === '--rehearse') {
    $resume = $argv[3] ?? null;
    if ($resume && ! preg_match('/^'.preg_quote($database, '/').'_rehearsal_[0-9]+$/', $resume)) {
        throw new RuntimeException('Resume must name this application rehearsal schema.');
    }
    $database = $resume ?? $database.'_rehearsal_'.date('mdHis');
}
if ($server->selectOne('SELECT COUNT(*) AS n FROM information_schema.schemata WHERE schema_name = ?', [$database])->n) {
    if ($mode !== '--rehearse' && $server->selectOne('SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = ?', [$database])->n) {
        throw new RuntimeException('Chosen schema is not empty.');
    }
} else {
    $server->statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
}
config(['database.connections.transfer_source' => array_replace(config('database.connections.sqlite'), ['url' => null, 'database' => $source]), 'database.connections.mysql' => array_replace($mysql, ['database' => $database]), 'database.default' => 'mysql']);
DB::purge('mysql');
$target = DB::connection('mysql');
$sqlite = DB::connection('transfer_source');
if ($sqlite->selectOne('PRAGMA integrity_check')->integrity_check !== 'ok' || $sqlite->select('PRAGMA foreign_key_check')) {
    throw new RuntimeException('Source integrity check failed.');
}
try {
    $schemaFrom = $mode === '--transfer' ? ($argv[3] ?? null) : null;
    if ($schemaFrom) {
        if (! preg_match('/^'.preg_quote((string) env('MYSQL_MIGRATION_DATABASE', 'foundation_management'), '/').'_rehearsal_[0-9]+$/', $schemaFrom)) {
            throw new RuntimeException('Use a verified rehearsal schema.');
        }
        $target->statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($server->select('SELECT TABLE_NAME AS name FROM information_schema.tables WHERE table_schema=?', [$schemaFrom]) as $table) {
                $ddl = (array) $server->selectOne('SHOW CREATE TABLE `'.$schemaFrom.'`.`'.$table->name.'`');
                $target->statement(str_replace('`'.$schemaFrom.'`.', '`'.$database.'`.', $ddl['Create Table']));
            }
            $target->table('migrations')->insert(array_map(fn ($row) => (array) $row, $server->table($schemaFrom.'.migrations')->get()->all()));
        } finally {
            $target->statement('SET FOREIGN_KEY_CHECKS=1');
        }
    } elseif (Artisan::call('migrate', ['--database' => 'mysql', '--force' => true]) !== 0) {
        throw new RuntimeException('Migration command failed.');
    }
} catch (Throwable $e) {
    // Schema diagnostics contain no user records or credentials.
    fwrite(STDERR, 'Schema migration failed in '.$database.': '.$e->getMessage().PHP_EOL);
    exit(1);
}
$sourceTables = array_column($sqlite->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"), 'name');
$counts = [];
$values = 0;
$canonical = function ($value, $type) {
    if ($value === null) {
        return null;
    }
    if ($type === 'date') {
        $date = new DateTimeImmutable((string) $value);
        if ($date->format('His.u') !== '000000.000000') {
            throw new RuntimeException('A DATE column contains a non-midnight time; explicit review required.');
        }

        return $date->format('Y-m-d');
    }
    if ($type === 'json') {
        $v = json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
        $sort = function (&$v) use (&$sort) {
            if (is_array($v)) {
                if (! array_is_list($v)) {
                    ksort($v);
                }foreach ($v as &$item) {
                    $sort($item);
                }
            }
        };
        $sort($v);

        return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }
    if (in_array($type, ['decimal', 'numeric'])) {
        return (string) BigDecimal::of((string) $value)->strippedOfTrailingZeros();
    }
    if (in_array($type, ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'])) {
        return (string) (int) $value;
    }

    return (string) $value;
};
$target->statement('SET FOREIGN_KEY_CHECKS=0');
try {
    $target->transaction(function () use ($sourceTables, $sqlite, $target, $database, $canonical, &$counts, &$values) {
        foreach ($sourceTables as $table) {
            if ($table === 'migrations') {
                continue;
            }
            $columns = $target->select('SELECT COLUMN_NAME name,DATA_TYPE type,NUMERIC_SCALE scale FROM information_schema.columns WHERE table_schema=? AND table_name=? ORDER BY ORDINAL_POSITION', [$database, $table]);
            if (! $columns) {
                throw new RuntimeException('Target table missing: '.$table);
            }
            $sourceColumns = array_column($sqlite->select('PRAGMA table_info("'.$table.'")'), 'name');
            $targetColumns = array_column($columns, 'name');
            sort($sourceColumns);
            $sorted = $targetColumns;
            sort($sorted);
            if ($sourceColumns !== $sorted) {
                throw new RuntimeException('Column mismatch: '.$table);
            }
            $select = [];
            foreach ($columns as $column) {
                $quoted = '"'.$column->name.'"';
                $select[] = in_array($column->type, ['decimal', 'numeric']) ? 'CASE WHEN '.$quoted.' IS NULL THEN NULL ELSE printf(\'%.'.(int) $column->scale.'f\', '.$quoted.') END AS '.$quoted : $quoted;
            }
            $rows = $sqlite->select('SELECT '.implode(',', $select).' FROM "'.$table.'"');
            // The schema is freshly migrated; replace only its bootstrap rows with snapshot rows.
            $target->table($table)->delete();
            foreach (array_chunk($rows, 100) as $chunk) {
                $target->table($table)->insert(array_map(fn ($row) => (array) $row, $chunk));
            }
            $expected = [];
            foreach ($rows as $row) {
                $r = [];
                foreach ($columns as $col) {
                    $r[$col->name] = $canonical($row->{$col->name}, $col->type);
                    $values++;
                }$expected[] = hash('sha256', json_encode($r, JSON_THROW_ON_ERROR));
            }
            $actual = [];
            foreach ($target->table($table)->get() as $row) {
                $r = [];
                foreach ($columns as $col) {
                    $r[$col->name] = $canonical($row->{$col->name}, $col->type);
                }$actual[] = hash('sha256', json_encode($r, JSON_THROW_ON_ERROR));
            }
            sort($expected);
            sort($actual);
            if ($expected !== $actual) {
                $different = [];
                $actualRows = $target->table($table)->get()->keyBy('id');
                foreach ($rows as $row) {
                    if (! isset($row->id) || ! $actualRows->has($row->id)) {
                        continue;
                    }
                    foreach ($columns as $col) {
                        if ($canonical($row->{$col->name}, $col->type) !== $canonical($actualRows[$row->id]->{$col->name}, $col->type)) {
                            $different[$col->name] = true;
                        }
                    }
                }
                throw new RuntimeException('Value verification failed: '.$table.'; columns: '.implode(',', array_keys($different)));
            }
            $counts[$table] = count($rows);
        }
        $keys = $target->select('SELECT TABLE_NAME t,COLUMN_NAME c,REFERENCED_TABLE_NAME rt,REFERENCED_COLUMN_NAME rc FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=? AND REFERENCED_TABLE_NAME IS NOT NULL', [$database]);
        foreach ($keys as $key) {
            $sql = 'SELECT COUNT(*) AS n FROM `'.$key->t.'` child LEFT JOIN `'.$key->rt.'` parent ON child.`'.$key->c.'`=parent.`'.$key->rc.'` WHERE child.`'.$key->c.'` IS NOT NULL AND parent.`'.$key->rc.'` IS NULL';
            if ($target->selectOne($sql)->n) {
                throw new RuntimeException('Foreign key verification failed: '.$key->t.'.'.$key->c);
            }
        }
    });
} catch (Throwable $e) {
    $detail = $e instanceof QueryException ? 'SQLSTATE '.$e->getCode().', driver '.($e->errorInfo[1] ?? 'unavailable') : $e->getMessage();
    fwrite(STDERR, 'Transfer failed ('.get_class($e).'). Target: '.$database.'. '.$detail.PHP_EOL);
    exit(1);
} finally {
    $target->statement('SET FOREIGN_KEY_CHECKS=1');
}
// Keep issued IDs above SQLite's high-water marks, including deleted rows.
foreach ($sqlite->select('SELECT name, seq FROM sqlite_sequence') as $sequence) {
    if (! array_key_exists($sequence->name, $counts)) {
        continue;
    }
    $auto = $target->selectOne('SELECT COLUMN_NAME AS name FROM information_schema.columns WHERE table_schema=? AND table_name=? AND EXTRA LIKE ?', [$database, $sequence->name, '%auto_increment%']);
    if ($auto) {
        $next = max((int) $sequence->seq, (int) $target->table($sequence->name)->max($auto->name)) + 1;
        $current = $target->selectOne('SELECT AUTO_INCREMENT AS next_id FROM information_schema.tables WHERE table_schema=? AND table_name=?', [$database, $sequence->name]);
        if ((int) $current->next_id < $next) {
            $target->statement('ALTER TABLE `'.$sequence->name.'` AUTO_INCREMENT = '.$next);
        }
    }
}
$receipt = ['database' => $database, 'mode' => $mode, 'tables' => count($counts), 'rows' => array_sum($counts), 'values_verified' => $values, 'counts' => $counts, 'source_sha256' => hash_file('sha256', $source), 'foreign_keys' => 'verified', 'passwords_and_ciphertext' => 'unchanged'];
file_put_contents($backup.'/mysql-'.($mode === '--rehearse' ? 'rehearsal' : 'transfer').'-verification.json', json_encode($receipt, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode(array_diff_key($receipt, ['counts' => true]), JSON_PRETTY_PRINT).PHP_EOL;
