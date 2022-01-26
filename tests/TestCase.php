<?php

namespace Makeable\ApiEndpoints\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Route;
use Makeable\ApiEndpoints\Tests\Stubs\ServerController;
use Makeable\ApiEndpoints\Tests\Stubs\UserController;
use Spatie\QueryBuilder\QueryBuilderServiceProvider;

class TestCase extends BaseTestCase
{
    /**
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        putenv('APP_ENV=testing');
        putenv('APP_DEBUG=true');

        $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        $app->useDatabasePath(__DIR__.'/database');
        $app->register(QueryBuilderServiceProvider::class);

//        $app['config']->set('database.default', 'sqlite');
//        $app['config']->set('database.connections.sqlite.database', ':memory:');

        $this->registerRoutes();

        return $app;
    }

    protected function registerRoutes()
    {
        Route::get('users', [UserController::class, 'index'])->name('api.users.index');
        Route::get('servers', [ServerController::class, 'index'])->name('api.servers.index');
    }
}
