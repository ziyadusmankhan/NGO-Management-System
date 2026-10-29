<?php

use Illuminate\Contracts\Console\Kernel;

// Run before activation; retains a verified database AND byte-for-byte upload snapshot.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || DB::connection()->getDriverName() !== 'sqlite') {
    throw new RuntimeException('Local SQLite backup only.');
}
$root = storage_path('app/backups/phase11-'.now('Asia/Karachi')->format('Ymd-His'));
if (is_dir($root)) {
    throw new RuntimeException('Backup destination already exists.');
}
mkdir($root, 0700, true);
$source = new SQLite3(DB::connection()->getDatabaseName(), SQLITE3_OPEN_READONLY);
$backup = new SQLite3($root.'/database.sqlite');
if (! $source->backup($backup) || $backup->querySingle('PRAGMA integrity_check') !== 'ok' || $backup->query('PRAGMA foreign_key_check')->fetchArray()) {
    throw new RuntimeException('Backup verification failed.');
}
$backup->close();
$source->close();
$manifest = ['database_sha256' => hash_file('sha256', $root.'/database.sqlite'), 'files' => []];
foreach (['private', 'public'] as $disk) {
    $from = storage_path('app/'.$disk);
    if (! is_dir($from)) {
        continue;
    }
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS)) as $file) {
        if (! $file->isFile() || $file->isLink()) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($from) + 1));
        $target = $root.'/uploads/'.$disk.'/'.$relative;
        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0700, true);
        }
        if (! copy($file->getPathname(), $target) || hash_file('sha256', $target) !== hash_file('sha256', $file->getPathname())) {
            throw new RuntimeException('Upload backup verification failed.');
        }
        $manifest['files'][$disk.'/'.$relative] = hash_file('sha256', $target);
    }
}
file_put_contents($root.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode(['backup' => $root, 'integrity' => 'ok', 'verified_files' => count($manifest['files']), 'contains_pre_upgrade_plaintext' => true], JSON_PRETTY_PRINT);
