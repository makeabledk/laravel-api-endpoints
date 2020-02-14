<?php

namespace Makeable\ApiEndpoints\Tests\Stubs\Endpoints;

use Makeable\ApiEndpoints\BaseEndpointRequest;
use Makeable\ApiEndpoints\Endpoint;
use Makeable\ApiEndpoints\Tests\Stubs\Database;

class DatabaseEndpoint extends Endpoint
{
    public $model = Database::class;
}
