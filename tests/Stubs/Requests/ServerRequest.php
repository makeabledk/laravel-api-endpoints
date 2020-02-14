<?php

namespace Makeable\ApiEndpoints\Tests\Stubs\Requests;

use Makeable\ApiEndpoints\BaseEndpointRequest;
use Makeable\ApiEndpoints\Endpoint;
use Makeable\ApiEndpoints\Tests\Stubs\Server;
use Spatie\QueryBuilder\AllowedFilter;

class ServerRequest extends BaseEndpointRequest
{
    /**
     * @return Endpoint
     */
    public static function endpoint()
    {
        return Endpoint::for(Server::class)
            ->allowedAppends([
                'status' => function ($query) {
                    $query->selectRaw('"active" as "status"');
                },
            ])
            ->allowedFilters([
                AllowedFilter::scope('favoured'),
            ])
            ->allowedIncludes([
                'databases' => DatabaseRequest::endpoint(),
            ])
            ->defaultSort('sort_order');
    }
}
