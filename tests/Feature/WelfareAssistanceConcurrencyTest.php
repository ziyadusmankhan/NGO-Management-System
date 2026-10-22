<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\Support\CreatesWelfareFixtures;
use Tests\TestCase;

class WelfareAssistanceConcurrencyTest extends TestCase
{
    use CreatesWelfareFixtures;

    public function test_two_simultaneous_creations_produce_one_assistance_and_one_history_entry(): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'welfare-phase1-'.Str::uuid();
        File::makeDirectory($directory);
        $database = $directory.DIRECTORY_SEPARATOR.'concurrency.sqlite';
        touch($database);
        $processes = [];
        try {
            config(['database.connections.sqlite.database' => $database, 'database.connections.sqlite.busy_timeout' => 10000, 'database.connections.sqlite.journal_mode' => 'WAL', 'database.connections.sqlite.synchronous' => 'OFF']);
            DB::purge('sqlite');
            $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
            config(['filesystems.disks.private.root' => $directory.DIRECTORY_SEPARATOR.'evidence']);
            Storage::forgetDisk('private');
            $actor = $this->staff();
            $application = $this->signedApplication($actor);
            $release = $directory.DIRECTORY_SEPARATOR.'release';

            // Child PHP processes use only the extensions this test needs; no debugger hooks.
            $php = [PHP_BINARY, '-n'];
            foreach (['mbstring', 'pdo_sqlite', 'sqlite3', 'fileinfo', 'openssl'] as $extension) {
                $path = ini_get('extension_dir').DIRECTORY_SEPARATOR.(PHP_OS_FAMILY === 'Windows' ? 'php_' : '').$extension.'.'.PHP_SHLIB_SUFFIX;
                if (! is_file($path)) { $path = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.$path; }
                if (is_file($path)) {
                    array_push($php, '-d', 'extension='.$path);
                }
            }
            for ($i = 0; $i < 2; $i++) {
                $process = new Process(array_merge($php, [
                    base_path('tests/Support/create_assistance_worker.php'), $database,
                    config('filesystems.disks.private.root'), (string) $application->id, (string) $actor->id,
                    $directory.DIRECTORY_SEPARATOR.'ready-'.$i, $release,
                ]), base_path(), ['APP_ENV' => 'testing']);
                $process->setTimeout(45);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 30;
            while (! file_exists($directory.DIRECTORY_SEPARATOR.'ready-0') || ! file_exists($directory.DIRECTORY_SEPARATOR.'ready-1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Workers failed to become ready: '.implode(' ', array_map(fn ($p) => $p->getErrorOutput().$p->getOutput(), $processes)));
                }
                usleep(10000);
                clearstatcache();
            }
            touch($release);
            $results = [];
            foreach ($processes as $process) {
                $this->assertSame(0, $process->wait(), $process->getErrorOutput().$process->getOutput());
                $results[] = trim($process->getOutput());
            }
            sort($results);
            $this->assertSame(['CREATED', 'DUPLICATE'], $results);
            $this->assertDatabaseCount('welfare_assistances', 1);
            $this->assertDatabaseCount('welfare_assistance_status_changes', 1);
            $this->assertDatabaseCount('welfare_assistance_payments', 0);
            $this->assertDatabaseCount('transactions', 0);
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            DB::purge('sqlite');
            Storage::forgetDisk('private');
            // Exact directory created above, never an application database or user folder.
            $resolved = realpath($directory);
            if ($resolved && dirname($resolved) === realpath(sys_get_temp_dir())
                && str_starts_with(basename($resolved), 'welfare-phase1-')) {
                File::deleteDirectory($resolved);
            }
        }
    }
}
