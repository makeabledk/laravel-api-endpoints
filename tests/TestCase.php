<?php

namespace Makeable\ApiEndpoints\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Route;
use Makeable\ApiEndpoints\EndpointServiceProvider;
use Makeable\ApiEndpoints\Tests\Stubs\Controllers\ServerController;
use Makeable\ApiEndpoints\Tests\Stubs\Controllers\TeamController;
use Makeable\ApiEndpoints\Tests\Stubs\Controllers\UserController;
use Spatie\QueryBuilder\QueryBuilderServiceProvider;

class TestCase extends BaseTestCase
{
    /**
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        $app->useDatabasePath(__DIR__.'/database');
        $app->register(EndpointServiceProvider::class);
        $app->register(QueryBuilderServiceProvider::class);

        $this->registerRoutes();

        return $app;
    }

    protected function registerRoutes()
    {
        Route::get('users', [UserController::class, 'index'])->name('api.users.index');
        Route::get('servers', [ServerController::class, 'index'])->name('api.servers.index');
        Route::get('teams', [TeamController::class, 'index'])->name('api.teams.index');
    }
}
