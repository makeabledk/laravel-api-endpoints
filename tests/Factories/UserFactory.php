<?php

namespace Makeable\ApiEndpoints\Tests\Factories;

use Makeable\ApiEndpoints\Tests\Stubs\User;
use Makeable\LaravelFactory\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'password' => 'secret',
        ];
    }
}
