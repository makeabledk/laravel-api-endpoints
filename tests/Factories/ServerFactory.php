<?php

namespace Makeable\ApiEndpoints\Tests\Factories;

use Makeable\ApiEndpoints\Tests\Stubs\Server;
use Makeable\ApiEndpoints\Tests\Stubs\User;
use Makeable\LaravelFactory\Factory;

class ServerFactory extends Factory
{
    protected $model = Server::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
        ];
    }
}
