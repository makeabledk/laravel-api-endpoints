<?php

namespace Makeable\ApiEndpoints\Tests\Stubs\Endpoints;

use Makeable\ApiEndpoints\Endpoint;
use Makeable\ApiEndpoints\Tests\Stubs\Server;
use Spatie\QueryBuilder\AllowedFilter;

class ServerEndpoint extends Endpoint
{
    public $model = Server::class;

    /**
     * @return Endpoint
     */
    public function __invoke()
    {
        $this
            ->allowedAppends([
                'status' => function ($query) {
                    $query->selectRaw('"active" as "status"');
                },
            ])
            ->allowedFilters([
                AllowedFilter::scope('favoured'),
            ])
            ->allowedIncludes([
                'databases' => DatabaseEndpoint::make(),
            ])
            ->defaultSort('sort_order');
    }
}
