<?php

namespace Makeable\ApiEndpoints\Tests\Stubs\Endpoints;

use Makeable\ApiEndpoints\Endpoint;
use Makeable\ApiEndpoints\Tests\Stubs\User;
use Spatie\QueryBuilder\AllowedInclude;

class UserEndpoint extends Endpoint
{
    public $model = User::class;

    public function __invoke()
    {
        $this
            ->allowedIncludes([
                'servers' => ServerEndpoint::make(),
                'favoriteServers' => ServerEndpoint::make(),
                // Newer Spatie syntax is currently not supported.
                //                AllowedInclude::relationship('teams'),
            ]);
    }
}
