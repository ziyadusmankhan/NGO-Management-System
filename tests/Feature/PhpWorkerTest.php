<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Tests\Support\PhpWorker;

class PhpWorkerTest extends TestCase
{
    public function test_isolated_worker_loads_pdo_and_can_query_sqlite(): void
    {
        $process = new Process(array_merge(PhpWorker::command(), ['-r', <<<'PHP'
            $pdo = new PDO('sqlite::memory:');
            $pdo->exec('CREATE TABLE probe (value INTEGER)');
            $pdo->exec('INSERT INTO probe VALUES (42)');
            echo json_encode(['value' => (int) $pdo->query('SELECT value FROM probe')->fetchColumn(),
                'extensions' => array_map('extension_loaded', ['mbstring', 'pdo_sqlite', 'sqlite3', 'fileinfo', 'openssl'])]);
            PHP]));
        $process->setTimeout(15)->run();
        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput().$process->getOutput());
        $this->assertSame('', $process->getErrorOutput());
        $this->assertSame(['value' => 42, 'extensions' => [true, true, true, true, true]], json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR));
    }
}
