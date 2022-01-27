<?php

namespace Makeable\ApiEndpoints;

use Illuminate\Support\Str;

trait NormalizesRelationNames
{
    /**
     * @param string $relation
     * @return string
     */
    protected function normalizeRelationName(string $relation): string
    {
        return Str::camel($relation);
    }
}