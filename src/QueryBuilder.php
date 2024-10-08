<?php

namespace Makeable\ApiEndpoints;

use Closure;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Makeable\ApiEndpoints\Concerns\AddsAppendsToQuery;
use Makeable\ApiEndpoints\Concerns\NormalizesRelationNames;
use Spatie\QueryBuilder\QueryBuilder as SpatieBuilder;

class QueryBuilder extends SpatieBuilder
{
    use AddsAppendsToQuery,
        NormalizesRelationNames {
            allowedAppends as originalAllowedAppends;
        }

    /**
     * @var array
     */
    protected $queuedConstraints = [];

    public function __call($name, $arguments)
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
     * @param  Request|null  $request
     * @return QueryBuilder
     */
    protected function initializeRequest(?Request $request = null): static
    {
        $this->request = $request
            ? QueryBuilderRequest::fromRequest($request)
            : app(QueryBuilderRequest::class);

        return $this;
    }

    /**
     * @param  $appends
     * @return QueryBuilder
     */
    public function allowedAppends($appends): static
    {
        collect($appends)
            ->mapWithKeys(Closure::fromCallable([$this, 'normalizeRelationQueries']))
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

    /**
     * Recursively set appends on nested eloquent models.
     *
     * @param  Collection  $results
     * @param  Collection|null  $appends
     * @return mixed
     */
    protected function addAppendsToResults(Collection $results, Collection $appends = null)
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
    public function allowedIncludes($includes): static
    {
        collect($includes)
            ->mapWithKeys(Closure::fromCallable([$this, 'normalizeRelationQueries']))
            ->mapWithKeys(fn ($constraints, $relation) => [$this->normalizeRelationName($relation) => $constraints])
            ->tap(function (Collection $includes) {
                $this->queueConstraints($includes);

                parent::allowedIncludes($includes->keys()->all());
            });

        return $this;
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

    /**
     * @param  $constraints
     * @param  $relation
     * @return array
     */
    protected function normalizeRelationQueries($constraints, $relation): array
    {
        if (is_numeric($relation)) {
            [$constraints, $relation] = [[], $constraints];
        }

        return [$relation => $constraints];
    }
}
