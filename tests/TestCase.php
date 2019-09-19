<?php

namespace Makeable\ApiEndpoints\Tests;


use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return \Illuminate\Foundation\Application|mixed|void
     */
    public function getEnvironmentSetUp($app)
    {
        putenv('APP_ENV=testing');
        putenv('APP_DEBUG=true');

        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
