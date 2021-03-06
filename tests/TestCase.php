<?php

namespace Makeable\ApiEndpoints\Tests;

use Faker\Generator;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Route;
use Makeable\ApiEndpoints\Tests\Stubs\Server;
use Makeable\ApiEndpoints\Tests\Stubs\ServerController;
use Makeable\ApiEndpoints\Tests\Stubs\User;
use Makeable\ApiEndpoints\Tests\Stubs\UserController;
use Makeable\LaravelFactory\Factory;
use Makeable\LaravelFactory\FactoryBuilder;
use Makeable\LaravelFactory\FactoryServiceProvider;
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
        $app->register(FactoryServiceProvider::class);

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        $this->registerRoutes();
        $this->setupModelFactories();

        return $app;
    }

    /**
     * @param null $class
     * @return Factory | FactoryBuilder
     */
    protected function factory($class = null)
    {
        $factory = app(Factory::class);

        if ($class) {
            return $factory->of($class);
        }

        return $factory;
    }

    protected function registerRoutes()
    {
        Route::get('users', [UserController::class, 'index'])->name('api.users.index');
        Route::get('servers', [ServerController::class, 'index'])->name('api.servers.index');
    }

    protected function setupModelFactories()
    {
        $this->factory()->define(User::class, function (Generator $faker) {
            return [
                'name' => $faker->name,
                'email' => $faker->email,
                'password' => 'secret',
            ];
        });

        $this->factory()->define(Server::class, function (Generator $faker) {
            return [
                'name' => $faker->name,
            ];
        });
    }
}
