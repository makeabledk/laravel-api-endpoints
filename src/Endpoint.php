<?php

namespace Makeable\ApiEndpoints;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Endpoint
{
    /**
     * The Spatie Query Builder implementation to be used.
     *
     * @var string
     */
    public static $queryBuilderClass = QueryBuilder::class;

    /**
     * How many levels of nested endpoints should be supported.
     * This is necessary to prevent infinite recursion
     * if some endpoints references each other.
     *
     * @var int
     */
    public static $maxEndpointDepth = 5;

    /**
     * The eloquent query builder methods which will trigger the Endpoint
     * to automatically convert into a QueryBuilder instance.
     *
     * @var array
     */
    public static $queryBuilderGetters = [
        'chunk', 'each', 'first', 'firstOrFail', 'get', 'getQuery', 'paginate', 'simplePaginate',
    ];

    /**
     * @var string
     */
    public $model;

    protected $namespace;

    protected $buffer = [];

    protected $endpoints = [];

    /**
     * @return static
     */
    public static function make()
    {
        return new static;
    }

    /**
     * @param $model
     * @return Endpoint
     */
    public static function for($model)
    {
        return tap(new static, function ($endpoint) use ($model) {
            $endpoint->model = $model;
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

        $this->buffer['calls'][] = function ($query) use ($method, $arguments) {
            // The Spatie Query Builder provides extra methods such as 'defaultSort', 'allowedFilters' etc.
            // These methods may be called directly on the endpoint, eg: Endpoint::for(...)->defaultSort('foo').

            // However, these methods do not work when endpoints are nested within each-other as the $query will not be
            // a Spatie QueryBuilder instance, but a Relation instance. The only thing we can do is to ignore the calls.
            // If the methods needs to be supported, we'll have to implement nested support for them individually.
            if ($query instanceof static::$queryBuilderClass || $this->isEloquentMethod($method)) {
                $query->$method(...$arguments);
            }
        };

        return $this;
    }

    public function __invoke()
    {

    }

    /**
     * @param $appends
     * @return Endpoint
     */
    public function allowedAppends($appends)
    {
        $this->buffer['appends'] = collect($appends)
            ->pipe(Closure::fromCallable([$this, 'buildNamespacedConstraintArrays']));

        return $this;
    }

    /**
     * @param $includes
     * @return Endpoint
     */
    public function allowedIncludes($includes)
    {
        $this->buffer['includes'] = collect($includes)
            ->filter(function ($constraint, $relation) {
                if ($constraint instanceof self) {
                    $this->endpoints[$relation] = $constraint;

                    return false;
                }

                return true;
            })
            ->pipe(Closure::fromCallable([$this, 'buildNamespacedConstraintArrays']));

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

    public function getQuery()
    {
        return $this->toQueryBuilder();
    }

    /**
     * @param  Request|null  $request
     * @return QueryBuilder|\Illuminate\Database\Query\Builder
     */
    public function toQueryBuilder(Request $request = null)
    {
        $builder = call_user_func([static::$queryBuilderClass, 'for'], $this->model, $request);

        [$calls, $appends, $includes] = $this->resolve();

        return $builder
            ->allowedAppends($appends)
            ->allowedIncludes($includes)
            ->tap(function ($query) use ($calls) {
                foreach ($calls as $call) {
                    $call($query);
                }
            });
    }

    // _________________________________________________________________________________________________________________

    /**
     * @param  int  $depth
     * @return array
     */
    public function resolve($depth = 0)
    {
        call_user_func($this);

        $buffer = [
            [$this->namespace ?? '' => Arr::get($this->buffer, 'calls', [])],
            $this->getNamespacedBufferContents('appends'),
            $this->getNamespacedBufferContents('includes'),
        ];

        if ($depth < static::$maxEndpointDepth) {
            foreach ($this->endpoints as $name => $endpoint) {
                // Let nested endpoints resolve themselves. By cloning we'll address an odd
                // edge case where same endpoint instances references each other circularly.
                $endpointBuffer = (clone $endpoint)
                    ->setNamespace($this->namespaced($name))
                    ->resolve($depth + 1);

                foreach ($endpointBuffer as $type => $contents) {
                    $buffer[$type] = array_merge_recursive($buffer[$type], $contents);
                }
            }
        }

        // Finally when everything has been merged recursively, we'll take all relational calls
        // and merge them into their respective 'includes'. We'll only keep the root calls as
        // 'buffered calls', and return a flat array rather than namespaced.
        if ($this->namespace === null) {
            $buffer[2] = array_merge_recursive($buffer[2], Arr::except($buffer[0], ''));
            $buffer[0] = Arr::get($buffer[0], '', []);
        }

        return $buffer;
    }

    /**
     * @param  string  $type
     * @return array
     */
    protected function getNamespacedBufferContents($type)
    {
        return collect(Arr::get($this->buffer, $type, []))
            ->mapWithKeys(function ($value, $key) {
                return [$this->namespaced($key) => $value];
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
     * Normalize a relations array of which some may have constraints and some not.
     *
     * Returns collection of the format:
     *
     * [
     *     'posts' => [
     *         function ($query) { ... },
     *         [...]
     *     ].
     *     'posts.comments' => [
     *         function ($query) { ... }
     *     ]
     * ]
     *
     * @param array $relations
     * @return \Illuminate\Support\Collection
     */
    protected function buildNamespacedConstraintArrays($relations)
    {
        return collect($relations)->mapWithKeys(function ($constraint, $relation) {
            // We'll ensure that a relation is always buffered with a constraint
            // - even when none was given, eg: ->allowedIncludes(['posts']).
            // This makes it easier to merge recursively later on.
            if (is_string($constraint)) {
                $relation = $constraint;
                $constraint = function () {
                };
            }

            // However we'll allow for multiple constraints on the same relation.
            // Later on we'll apply all of the constraints into the same query.
            return [$relation => Arr::wrap($constraint)];
        });
    }
}
