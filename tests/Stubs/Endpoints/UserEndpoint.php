<?php

namespace Makeable\ApiEndpoints\Tests\Stubs\Endpoints;

use Makeable\ApiEndpoints\Endpoint;
use Makeable\ApiEndpoints\Tests\Stubs\User;

class UserEndpoint extends Endpoint
{
    public $model = User::class;

    public function __invoke()
    {
        $this
            ->allowedIncludes([
                'servers' => ServerEndpoint::make(),
            ]);
    }
}
