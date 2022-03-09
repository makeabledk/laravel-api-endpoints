<?php

namespace Makeable\ApiEndpoints;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Makeable\ApiEndpoints\Concerns\NormalizesRelationNames;

class QueryBuilderRequest extends \Spatie\QueryBuilder\QueryBuilderRequest
{
    use NormalizesRelationNames;

    public static function fromRequest(Request $request): self
    {
        return static::createFrom($request, new static());
    }

    public function includes(): Collection
    {
        return parent::includes()->map(fn ($relation) => $this->normalizeRelationName($relation));
    }
}
