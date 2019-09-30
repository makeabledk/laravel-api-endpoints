<?php


namespace Makeable\ApiEndpoints\Tests\Stubs\Requests;

use Spatie\QueryBuilder\Filter;
use Makeable\ApiEndpoints\Endpoint;
use Makeable\ApiEndpoints\Tests\Stubs\Server;
use Makeable\ApiEndpoints\BaseEndpointRequest;

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
                }
            ])
            ->allowedFilters([
                Filter::custom('favoured', ScopeFilter::make()),
            ])
            ->allowedIncludes([
                'databases'
            ])
            ->defaultSort('sort_order');
    }
}
