<?php

namespace Makeable\ApiEndpoints\Tests\Stubs\Endpoints;

use Makeable\ApiEndpoints\Endpoint;
use Makeable\ApiEndpoints\Tests\Stubs\Database;
use Spatie\QueryBuilder\AllowedFilter;

class DatabaseEndpoint extends Endpoint
{
    public $model = Database::class;

    public function __invoke()
    {
        $this
            ->allowedIncludes([
                'server' => ServerEndpoint::make(),
            ]);
    }
}
