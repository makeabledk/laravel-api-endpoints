<?php

namespace Makeable\ApiEndpoints;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseEndpointRequest extends FormRequest
{
    /**
     * @return Endpoint
     */
    abstract public static function endpoint();

    /**
     * @return Endpoint
     */
    public function getQuery()
    {
        return static::endpoint();
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [];
    }
}
