<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Every test uses isolated evidence, including fixtures that do not explicitly fake disks.
        Storage::fake('private');
        config(['filesystems.disks.local.root' => Storage::disk('private')->path('')]);
        Storage::forgetDisk('local');
    }

    public function actingAs(Authenticatable $user, $guard = null)
    {
        $user->refresh();
        parent::actingAs($user, $guard);
        $this->withSession(['security.version' => (int) ($user->session_version ?? 1), 'security.started' => time(), 'security.seen' => time(), 'security.complete' => true, 'security.confirmed' => time()]);

        return $this;
    }

    public function createApplication()
    {
        $app = parent::createApplication();

        // Fail before any migration if a cached configuration points at live data.
        if ($app->environment('testing') && getenv('MYSQL_INTEGRATION_TESTS') === '1') {
            $database=$app['config']->get('database.connections.mysql.database');
            $receiptPath=base_path('tmp/mysql-test-target.json');
            $receipt=is_file($receiptPath)?json_decode(file_get_contents($receiptPath),true):[];
            if($app['config']->get('database.default')!=='mysql'||$app['config']->get('database.connections.mysql.url')||!preg_match('/^foundation_management_test_[0-9]{14}_[a-f0-9]{4}$/',(string)$database)||($receipt['database']??null)!==$database||($receipt['schema_only']??false)!==true) {
                throw new \RuntimeException('MySQL tests require the explicitly prepared disposable schema; live databases are forbidden.');
            }
            // Schema is cloned from the verified current schema. Each test rolls back its writes.
            \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated=true;
        } elseif (! $app->environment('testing') || $app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Tests require the isolated in-memory SQLite database. Clear cached configuration first.');
        }

        return $app;
    }
}
