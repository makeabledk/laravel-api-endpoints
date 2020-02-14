<?php

namespace Makeable\ApiEndpoints;

use Illuminate\Http\Request;

abstract class BaseEndpointRequest extends Request
{
    /**
     * @return Endpoint
     */
    abstract public static function endpoint();

    /**
     * @return \Makeable\ApiEndpoints\Endpoint
     */
    public function getEndpoint()
    {
        return static::endpoint();
    }

    /**
     * @return \Makeable\ApiEndpoints\QueryBuilder
     */
    public function getQuery()
    {
        return $this->getEndpoint()->toQueryBuilder();
    }
}
