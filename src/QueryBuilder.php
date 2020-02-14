<?php

namespace Makeable\ApiEndpoints;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\QueryBuilder as SpatieBuilder;

class QueryBuilder extends SpatieBuilder
{
    /**
     * @var array
     */
    protected $queuedConstraints = [];

    /**
     * @param $appends
     * @return SpatieBuilder
     */
    public function allowedAppends($appends): SpatieBuilder
    {
        collect($appends)
            ->mapWithKeys(Closure::fromCallable([$this, 'normalizeRelationQueries']))
            ->tap(function (Collection $appends) {
                parent::allowedAppends($appends->keys()->all());
            })
            ->each(function ($constraints, $qualifiedField) {
                if ($this->request()->appends()->contains($qualifiedField)) {
                    if (($relation = $this->getNamespace($qualifiedField)) === '') {
                        // Add constraint on root appends
                        $this->tap($this->mergeConstraints(...$constraints)); // Apply constraint on this builder
                    } else {
                        // Add constraint on relational appends
                        $this->queueConstraints([$relation => $constraints]);
                    }
                }
            });

        return $this;
    }

    /**
     * Recursively set appends on nested eloquent models.
     *
     * @param Collection $results
     * @param Collection|null $appends
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
                    $this->addAppendsToResults($model->{$relation}, collect($appends));
                }
            });
            $model->append($rootAppends->toArray());
        });
    }

    /**
     * @param $includes
     * @return SpatieBuilder
     */
    public function allowedIncludes($includes): SpatieBuilder
    {
        collect($includes)
            ->mapWithKeys(Closure::fromCallable([$this, 'normalizeRelationQueries']))
            ->tap(function (Collection $includes) {
                $this->queueConstraints($includes);

                parent::allowedIncludes($includes->keys()->all());
            });

        return $this;
    }

    /**
     * @param array $models
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
     * @return $this
     */
    public function applyQueuedConstraints()
    {
        foreach ($this->eagerLoad as $relation => $base) {
            if (isset($this->queuedConstraints[$relation])) {
                $this->eagerLoad[$relation] = $this->mergeConstraints($base, ...$this->queuedConstraints[$relation]);
            }
        }

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
     * @param callable $callable
     * @return $this|\Illuminate\Database\Query\Builder
     */
    public function tap($callable)
    {
        call_user_func($callable, $this);

        return $this;
    }

    /**
     * @param $string
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
     * @param $relations
     * @return $this
     */
    protected function queueConstraints($relations)
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
     * @param mixed ...$constraints
     * @return Closure
     */
    protected function mergeConstraints(...$constraints)
    {
        return function ($query) use ($constraints) {
            foreach ($constraints as $constraint) {
                $constraint($query);
            }
        };
    }

    /**
     * @param $constraints
     * @param $relation
     * @return array
     */
    protected function normalizeRelationQueries($constraints, $relation)
    {
        if (is_numeric($relation)) {
            [$constraints, $relation] = [[], $constraints];
        }

        return [$relation => $constraints];
    }
}
