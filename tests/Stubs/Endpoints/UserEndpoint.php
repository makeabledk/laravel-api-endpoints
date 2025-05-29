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
                //                'serversCount' => AllowedInclude::count('servers'),
                'favoriteServers' => ServerEndpoint::make(),
                AllowedInclude::relationship('teams'),
            ]);
    }
}
