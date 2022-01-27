<?php

namespace Makeable\ApiEndpoints;

use Illuminate\Support\ServiceProvider;

class EndpointServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(QueryBuilderRequest::class, function ($app) {
            return QueryBuilderRequest::fromRequest($app['request']);
        });
    }
}
