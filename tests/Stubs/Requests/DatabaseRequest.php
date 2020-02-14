<?php

namespace Makeable\ApiEndpoints\Tests\Stubs\Requests;

use Makeable\ApiEndpoints\BaseEndpointRequest;
use Makeable\ApiEndpoints\Endpoint;
use Makeable\ApiEndpoints\Tests\Stubs\Database;
use Makeable\ApiEndpoints\Tests\Stubs\Server;
use Spatie\QueryBuilder\AllowedFilter;

class DatabaseRequest extends BaseEndpointRequest
{
    /**
     * @return Endpoint
     */
    public static function endpoint()
    {
        return Endpoint::for(Database::class);
    }
}
