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
                'databases_count' => function ($query) {
                    $query->withCount('databases');
                },
            ])
            ->allowedFilters([
                AllowedFilter::custom('favoured', ScopeFilter::make()),
            ])
            ->allowedIncludes([
                'databases',
            ])
            ->defaultSort('sort_order');
    }
}
