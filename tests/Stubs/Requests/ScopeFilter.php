<?php

namespace Makeable\ApiEndpoints\Tests\Stubs\Requests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\Filter;

class ScopeFilter implements Filter
{
    /**
     * @var array
     */
    protected $args = [];

    /**
     * @return ScopeFilter
     */
    public static function make()
    {
        return new static();
    }

    /**
     * @param $args
     * @return $this
     */
    public function args($args)
    {
        $this->args = Arr::wrap($args);

        return $this;
    }

    /**
     * @param Builder $query
     * @param $value
     * @param string $property
     * @return Builder
     */
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        $scope = Str::camel($property);

        return $query->$scope(...$this->args);
    }
}
