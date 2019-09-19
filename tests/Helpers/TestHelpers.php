<?php


namespace Makeable\ApiEndpoints\Tests\Helpers;

use App\User;

trait TestHelpers
{
    protected function set(...$args)
    {
        return $this;
    }

    /**
     * @param mixed $attributes
     * @return User
     */
    protected function user($attributes = [])
    {
        if (is_array($attributes)) {
            $attributes = function ($factoryBuilder) use ($attributes) {
                $factoryBuilder->fill($attributes);
            };
        }

        return factory(User::class)->tap($attributes)->create();
    }
}
