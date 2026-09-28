<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if ($app['config']->get('database.default') === 'mysql'
            && $app['config']->get('database.connections.mysql.database') !== 'insys_db_testing') {
            throw new RuntimeException('Safety stop: automated tests may only use the insys_db_testing database.');
        }

        return $app;
    }
}
