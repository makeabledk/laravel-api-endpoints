<?php

namespace Makeable\ApiEndpoints;

use Closure;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Makeable\ApiEndpoints\Concerns\AddsAppendsToQuery;
use Makeable\ApiEndpoints\Concerns\NormalizesRelationNames;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder as SpatieBuilder;

class QueryBuilder extends SpatieBuilder
{
    use AddsAppendsToQuery,
        NormalizesRelationNames {
            allowedAppends as originalAllowedAppends;
        }

    public function __construct(
        protected Relation|EloquentBuilder $subject,
        ?Request $request = null
    ) {
        $this->request = $request
            ? QueryBuilderRequest::fromRequest($request)
            : app(QueryBuilderRequest::class);
    }

    /**
     * @var array
     */
    protected $queuedConstraints = [];

    public function __call($name, $arguments): mixed
    {
        $this->applyQueuedConstraints();

        $result = parent::__call($name, $arguments);

        if ($result instanceof Model) {
            $this->addAppendsToResults(collect([$result]));
        }

        if ($result instanceof Collection) {
            $this->addAppendsToResults($result);
        }

        if ($result instanceof Paginator || $result instanceof CursorPaginator) {
            $this->addAppendsToResults(collect($result->items()));
        }

        return $result;
    }

    /**
     * @param  $appends
     * @return QueryBuilder
     */
    public function allowedAppends($appends): static
    {
        collect($appends)
            ->flatMap(fn ($constraints, $relation) => $this->normalizeRelationQueries($constraints, $relation))
            ->tap(function (Collection $appends) {
                $this->originalAllowedAppends($appends->keys()->all());
            })
            ->each(function ($constraints, $qualifiedField) {
                // Support nested appends with custom constraints on the relation.
                if ($this->request()->appends()->contains($qualifiedField)) {
                    if (($relation = $this->getNamespace($qualifiedField)) === '') {
                        // Add constraint on root appends
                        $this->tap($this->mergeConstraints(...$constraints)); // Apply constraint on this builder
                    } else {
                        // Add constraint on relational appends
                        $this->queueConstraints([$this->normalizeRelationName($relation) => $constraints]);
                    }
                }
            });

        return $this;
    }

    public function allowedFields(...$fields): static
    {
        return parent::allowedFields(...$this->normalizeVariadicArguments($fields));
    }

    public function allowedFilters(...$filters): static
    {
        return parent::allowedFilters(...$this->normalizeVariadicArguments($filters));
    }

    /**
     * Recursively set appends on nested eloquent models.
     *
     * @param  Collection  $results
     * @param  Collection|null  $appends
     * @return mixed
     */
    protected function addAppendsToResults(Collection $results, ?Collection $appends = null)
    {
        $appends = collect($appends ?: $this->request->appends());

        $namespacedAppends = $appends->mapToGroups(function ($attribute) {
            $relation = strpos($attribute, '.') !== false
                ? Str::before($attribute, '.')
                : '';

            return [$relation => Str::after($attribute, '.')];
        });

        // Get the appends used on the root resources.
        // Ignore appends already present in attributes.
        $rootAppends = collect($namespacedAppends->pull(''))->reject(function ($append) use ($results) {
            return Arr::has(optional($results->first())->getAttributes(), $append);
        });

        return $results->each(function ($model) use ($rootAppends, $namespacedAppends) {
            $namespacedAppends->each(function ($appends, $relation) use ($model) {
                if ($model->relationLoaded($relation)) {
                    $this->addAppendsToResults(Collection::wrap($model->{$relation}), collect($appends));
                }
            });
            $model->append($rootAppends->toArray());
        });
    }

    /**
     * @param  $includes
     * @return QueryBuilder
     */
    public function allowedIncludes(...$includes): static
    {
        $includes = count($includes) === 1 && is_array($includes[0])
            ? $includes[0]
            : $includes;

        collect($includes)
            ->flatMap(fn ($constraints, $relation) => $this->normalizeRelationQueries($constraints, $relation))
            ->mapWithKeys(fn ($constraints, $relation) => [$this->normalizeRelationName($relation) => $constraints])
            ->tap(function (Collection $includes) {
                $this->queueConstraints($includes);

                parent::allowedIncludes(...$includes->keys()->all());
            });

        return $this;
    }

    public function allowedSorts(...$sorts): static
    {
        return parent::allowedSorts(...$this->normalizeVariadicArguments($sorts));
    }

    public function defaultSort(...$sorts): static
    {
        return parent::defaultSort(...$this->normalizeVariadicArguments($sorts));
    }

    public function defaultSorts(...$sorts): static
    {
        return parent::defaultSorts(...$this->normalizeVariadicArguments($sorts));
    }

    /**
     * @param  array  $models
     * @return array
     */
    public function eagerLoadRelations(array $models)
    {
        $this->applyQueuedConstraints();

        return parent::eagerLoadRelations($models);
    }

    /**
     * Loop through the queued relational constraints and merge them into
     * one single constraint. Then set it to Laravel's eagerLoads so it
     * will be executed when the relation is eager-loaded.
     *
     * @return QueryBuilder
     */
    public function applyQueuedConstraints(): static
    {
        $eagerLoad = $this->subject->getEagerLoads();

        foreach ($eagerLoad as $relation => $base) {
            if (isset($this->queuedConstraints[$relation])) {
                $eagerLoad[$relation] = $this->mergeConstraints($base, ...$this->queuedConstraints[$relation]);

                $this->queuedConstraints[$relation] = [];
            }
        }

        $this->subject->setEagerLoads($eagerLoad);

        return $this;
    }

    /**
     * @return \Spatie\QueryBuilder\QueryBuilderRequest
     */
    public function request()
    {
        return $this->request;
    }

    /**
     * @param  callable  $callable
     * @return QueryBuilder
     */
    public function tap($callable): static
    {
        call_user_func($callable, $this);

        return $this;
    }

    /**
     * @param  $string
     * @return string
     */
    protected function getNamespace($string)
    {
        if (Str::contains($string, '.')) {
            $parts = explode('.', $string);
            array_pop($parts);

            return implode('.', $parts);
        }

        return '';
    }

    /**
     * @param  $relations
     * @return QueryBuilder
     */
    protected function queueConstraints($relations): static
    {
        $this->queuedConstraints = array_merge_recursive(
            $this->queuedConstraints,
            collect($relations)->mapWithKeys(function (array $constraints, $relation) {
                return [Str::camel($relation) => $constraints];
            })->all()
        );

        return $this;
    }

    /**
     * @param  mixed  ...$constraints
     * @return Closure
     */
    protected function mergeConstraints(...$constraints): Closure
    {
        return function ($query) use ($constraints) {
            foreach ($constraints as $constraint) {
                $constraint($query);
            }
        };
    }

    protected function normalizeVariadicArguments(array $arguments): array
    {
        if (count($arguments) === 1 && is_array($arguments[0])) {
            return array_values($arguments[0]);
        }

        return $arguments;
    }

    /**
     * @param  $constraints
     * @param  $relation
     * @return array
     */
    protected function normalizeRelationQueries($constraints, $relation): Collection
    {
//        Currently not working as intended
//        // Support AllowedInclude::relationship() which returns a Collection of AllowedInclude
//        if (is_numeric($relation) && is_array($constraints) && count($constraints) === 1 && $constraints[0] instanceof Collection) {
//            return $constraints[0]->mapWithKeys(function (AllowedInclude $include) {
//                return [$include->getName() => [fn ($query) => $include->include($query)]];
//            });
//        }

        if (is_numeric($relation) && is_string($constraints)) {
            [$constraints, $relation] = [[], $constraints];
        }

        return collect([$relation => $constraints]);
    }
}
