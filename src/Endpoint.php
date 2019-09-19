<?php

namespace Makeable\ApiEndpoints;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Endpoint
{
    public static $queryBuilderGetters = [
        'chunk', 'each', 'first', 'firstOrFail', 'get', 'getQuery', 'paginate', 'simplePaginate'
    ];

    protected $endpoints = [];

    protected $modelClass;

    protected $namespace;

    protected $resources = [];

    protected $queuedCalls = [];

    /**
     * @param $modelClass
     * @return Endpoint
     */
    public static function for($modelClass)
    {
        return tap(new static, function ($builder) use ($modelClass) {
            $builder->modelClass = $modelClass;
        });
    }

    /**
     * @param $appends
     * @return Endpoint
     */
    public function allowedAppends($appends)
    {
        $this->resources['appends'] = collect($appends)
            ->pipe(Closure::fromCallable([$this, 'normalizeResources']));

        return $this;
    }

    /**
     * @param $includes
     * @return Endpoint
     */
    public function allowedIncludes($includes)
    {
        $this->resources['includes'] = collect($includes)
            ->filter(function ($constraint, $relation) {
                if ($constraint instanceof Endpoint) {
                    $this->endpoints[$relation] = $constraint;

                    return false;
                }
                return true;
            })
            ->pipe(Closure::fromCallable([$this, 'normalizeResources']));

        return $this;
    }

    /**
     * @param $namespace
     * @return Endpoint
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @param $relation
     * @param $callable
     * @return mixed|QueryBuilder
     */
    public function whenIncluding($relation, $callable)
    {
        return $this->tap(function ($query) use ($relation, $callable) {
            // The way we check for included relations depends if
            // it is the root endpoint or a nested relationship.

            // When it is the root endpoint we know for sure that we have a
            // Spatie query builder instance. Therefore we can access
            // includes and check if it contains the queried relation
            if ($query instanceof QueryBuilder) {
                $isIncluding = $query->request()->includes()->first(function ($include) use ($relation) {
                        return Str::startsWith($include, $relation);
                    }) !== null;
            }
            // When dealing with nested relations we will not have the
            // includes() helper at our disposal. Instead we can check
            // on the compiled eager-loads if it has our relation name
            else {
                $isIncluding = Arr::has($query->getEagerLoads(), Str::camel($relation));
            }

            $query->when($isIncluding, $callable);
        });
    }

    /**
     * @param $method
     * @param $arguments
     * @return Endpoint
     */
    public function __call($method, $arguments)
    {
        if (in_array($method, static::$queryBuilderGetters)) {
            return $this->toQueryBuilder()->$method(...$arguments);
        }

        array_push($this->queuedCalls, function ($query) use ($method, $arguments) {
            // Queued calls are sometimes applied on eloquent relations as well
            // in which case we don't want to call Spatie specific methods
            // such as 'defaultSort' or 'allowedXYZ'
            if ($query instanceof QueryBuilder || $this->isEloquentMethod($method)) {
                $query->$method(...$arguments);
            }
        });

        return $this;
    }

    /**
     * @param Request|null $request
     * @return QueryBuilder|\Illuminate\Database\Query\Builder
     */
    public function toQueryBuilder(Request $request = null)
    {
        return QueryBuilder::for($this->modelClass, $request)
            ->tap(Closure::fromCallable([$this, 'applyRelations']))
            ->tap(Closure::fromCallable([$this, 'applyQueuedCalls']));
    }

    // _________________________________________________________________________________________________________________

    /**
     * @param QueryBuilder $builder
     */
    protected function applyRelations(QueryBuilder $builder)
    {
        [$appends, $includes] = [$this->getNamespacedResources('appends'), $this->getNamespacedResources('includes')];

        foreach ($this->endpoints as $name => $endpoint) {
            $related = $endpoint->setNamespace($this->namespaced($name));

            $appends = array_merge_recursive($appends, $related->getNamespacedResources('appends'));
            $includes = array_merge_recursive($includes, $related->getNamespacedResources('includes'), [
                $endpoint->namespace => $endpoint->queuedCalls // Add queued calls as relation constraint
            ]);
        }

        $builder->allowedAppends($appends);
        $builder->allowedIncludes($includes);
    }

    /**
     * @param QueryBuilder $query
     */
    protected function applyQueuedCalls(QueryBuilder $query)
    {
        foreach ($this->queuedCalls as $call) {
            $call($query);
        }
    }

    /**
     * @param $resourceType
     * @return array
     */
    protected function getNamespacedResources($resourceType)
    {
        return collect(Arr::get($this->resources, $resourceType, []))
            ->mapWithKeys(function ($value, $key) {
                return is_integer($key)
                    ? [$key => $this->namespaced($value)]
                    : [$this->namespaced($key) => $value];
            })
            ->toArray();
    }

    /**
     * @param $method
     * @return bool
     */
    protected function isEloquentMethod($method)
    {
        return ! collect(get_class_methods(QueryBuilder::class))
            ->flip()
            ->forget(get_class_methods(Builder::class))
            ->has($method);
    }

    /**
     * @param $name
     * @return string
     */
    protected function namespaced($name)
    {
        return $this->namespace
            ? $this->namespace.'.'.$name
            : $name;
    }

    /**
     * @param $resources
     * @return \Illuminate\Support\Collection
     */
    protected function normalizeResources($resources)
    {
        return collect($resources)->map(function ($constraint) {
            if (! is_string($constraint)) { // Constraint is given
                return Arr::wrap($constraint);
            }
            return $constraint; // Relation name given without constraint
        });
    }
}
