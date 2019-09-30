<?php


namespace Makeable\ApiEndpoints\Tests\Stubs\Requests;

use Makeable\ApiEndpoints\BaseEndpointRequest;
use Makeable\ApiEndpoints\Endpoint;
use Makeable\ApiEndpoints\Tests\Stubs\User;

class UserRequest extends BaseEndpointRequest
{
    /**
     * @return Endpoint
     */
    public static function endpoint()
    {
        return Endpoint::for(User::class)
            ->allowedIncludes([
                'servers' => ServerRequest::endpoint()
            ]);
    }
}
