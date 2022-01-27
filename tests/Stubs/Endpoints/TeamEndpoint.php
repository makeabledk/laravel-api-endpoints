<?php

namespace Makeable\ApiEndpoints\Tests\Stubs\Endpoints;

use Makeable\ApiEndpoints\Endpoint;
use Makeable\ApiEndpoints\Tests\Stubs\Team;

class TeamEndpoint extends Endpoint
{
    public $model = Team::class;

    public function __invoke()
    {
        $this
            ->allowedIncludes([
                'users' => UserEndpoint::make(),
            ]);
    }
}
