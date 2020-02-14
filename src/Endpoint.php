<?php

namespace Makeable\ApiEndpoints;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Endpoint
{
    public static $queryBuilderClass = QueryBuilder::class;

    public static $queryBuilderGetters = [
        'chunk', 'each', 'first', 'firstOrFail', 'get', 'getQuery', 'paginate', 'simplePaginate',
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
                if ($constraint instanceof self) {
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
            // Match Spatie's normalization to snake case
            $relation = Str::camel($relation);

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
                $isIncluding = Arr::has($query->getEagerLoads(), $relation);
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
        // For convenience we'll automatically convert the endpoint
        // to a query builder instance if we detect the developer
        // is attempting to get the results of the query.
        if (in_array($method, static::$queryBuilderGetters)) {
            return $this->toQueryBuilder()->$method(...$arguments);
        }

        array_push($this->queuedCalls, function ($query) use ($method, $arguments) {
            // Queued calls are sometimes applied on eloquent relations as well
            // in which case we don't want to call Spatie specific methods
            // such as 'defaultSort' or 'allowedXYZ'
            if ($query instanceof static::$queryBuilderClass || $this->isEloquentMethod($method)) {
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
        $builder = call_user_func([static::$queryBuilderClass, 'for'], $this->modelClass, $request);

        [$calls, $appends, $includes] = $this->getCompiledResources();

        return $builder
            ->allowedAppends($appends)
            ->allowedIncludes($includes)
            ->when(! empty($calls), function ($query) use ($calls) {
                foreach ($calls as $call) {
                    $call($query);
                }
            });
    }

    // _________________________________________________________________________________________________________________

    /**
     * @return array
     */
    protected function getCompiledResources()
    {
        $resources = [
            [$this->namespace ?? '' => $this->queuedCalls], // queued calls
            $this->getNamespacedResources('appends'), // appends
            $this->getNamespacedResources('includes'), // includes
        ];

        foreach ($this->endpoints as $name => $endpoint) {
            $endpointResources = $endpoint
                ->setNamespace($this->namespaced($name))
                ->getCompiledResources();

            foreach ($endpointResources as $type => $resource) {
                $resources[$type] = array_merge_recursive($resources[$type], $resource);
            }
        }

        // Finally when everything has been merged recursively, we'll take all relational queued calls
        // and merge them into their respective 'include constraints'. We'll only keep the root
        // constraints as 'queued calls', and return is a flat array rather than namespaced.
        if ($this->namespace === null) {
            $resources[2] = array_merge_recursive($resources[2], Arr::except($resources[0], ''));
            $resources[0] = Arr::get($resources[0], '', []);
        }

        return $resources;
    }

    /**
     * @param $resourceType
     * @return array
     */
    protected function getNamespacedResources($resourceType)
    {
        return collect(Arr::get($this->resources, $resourceType, []))
            ->mapWithKeys(function ($value, $key) {
                return is_int($key)
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
        return ! collect(get_class_methods(static::$queryBuilderClass))
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
        return collect($resources)->mapWithKeys(function ($constraint, $relation) {
            // We'll ensure that a resource is always queued with a constraint
            // - even when none was given, eg: ->allowedIncludes(['posts']).
            // This makes it easier to merge recursively later on.
            if (is_string($constraint)) {
                $relation = $constraint;
                $constraint = function () {};
            }

            // However we'll allow for multiple constraints on the same relation.
            // Later on we'll apply all of the constraints into the same query.
            return [$relation => Arr::wrap($constraint)];
        });
    }
}
