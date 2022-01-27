<?php

namespace Makeable\ApiEndpoints\Tests\Factories;

use Makeable\ApiEndpoints\Tests\Stubs\Team;
use Makeable\LaravelFactory\Factory;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
        ];
    }
}
