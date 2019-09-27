<?php

namespace Makeable\ApiEndpoints\Tests;

use Illuminate\Contracts\Console\Kernel;
use Makeable\ApiEndpoints\Tests\Helpers\TestHelpers;
use Spatie\QueryBuilder\QueryBuilderServiceProvider;

class UnitTestCase extends \Illuminate\Foundation\Testing\TestCase
{
    use TestHelpers;

    /**
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        putenv('APP_ENV=testing');
        putenv('APP_DEBUG=true');

        $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        $app->register(QueryBuilderServiceProvider::class);
        $app->afterResolving('migrator', function ($migrator) {
            $migrator->path(__DIR__.'/migrations/');
        });

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        return $app;
    }
}
