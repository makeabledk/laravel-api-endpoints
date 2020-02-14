<?php

namespace Makeable\ApiEndpoints\Tests\Feature;

use Illuminate\Http\Request;
use Makeable\ApiEndpoints\Endpoint;
use Makeable\ApiEndpoints\Tests\Stubs\Database;
use Makeable\ApiEndpoints\Tests\Stubs\Server;
use Makeable\ApiEndpoints\Tests\Stubs\User;
use Makeable\ApiEndpoints\Tests\TestCase;
use Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery;

class EndpointUnitTest extends TestCase
{
    /** @test **/
    public function it_accepts_constraints_when_defining_includes()
    {
        $endpoint = Endpoint::for(User::class)->allowedIncludes([
            'servers' => $this->invokable(),
        ]);

        $this->expectInvoked();
        $this->request($endpoint, ['include' => 'servers']);
    }

    /** @test **/
    public function it_adapts_namespaced_appends_and_includes_when_adding_another_endpoint()
    {
        $endpoint = Endpoint::for(User::class)
            ->allowedAppends(['full_name'])
            ->allowedIncludes([
                'teams',
                'servers' => Endpoint::for(Server::class)
                    ->allowedAppends(['ip'])
                    ->allowedIncludes(['databases']),
            ]);

        $query = $this->request($endpoint, [
            'append' => 'full_name,servers.ip',
            'include' => 'teams,servers.databases',
        ]);

        $this->assertArrayHasKey('teams', $query->getEagerLoads());
        $this->assertArrayHasKey('servers', $query->getEagerLoads());
        $this->assertArrayHasKey('servers.databases', $query->getEagerLoads());
    }

    /** @test **/
    public function regression_it_supports_deeply_nested_endpoints()
    {
        $endpoint = Endpoint::for(User::class)
            ->tap(function ($q) {
            })
            ->allowedAppends('full_name')
            ->allowedIncludes([
                'servers' => Endpoint::for(Server::class)
                    ->tap(function ($q) {
                    })
                    ->allowedAppends(['ip'])
                    ->allowedIncludes([
                        'databases' => Endpoint::for(Database::class)
                            ->tap(function ($q) {
                            })
                            ->allowedAppends(['tables' => function ($q) {
                            }]),
                    ]),
            ]);

        $query = $this->request($endpoint, [
            'include' => 'servers.databases',
            'append' => 'full_name,servers.ip,servers.databases.tables',
        ]);

        $this->assertArrayHasKey('servers', $query->getEagerLoads());
        $this->assertArrayHasKey('servers.databases', $query->getEagerLoads());
    }

    /** @test **/
    public function regression_it_protects_against_infinite_recursion_on_circular_referenced_endpoints()
    {
        $userEndpoint = Endpoint::for(User::class);
        $serverEndpoint = Endpoint::for(Server::class)->allowedIncludes(['user' => $userEndpoint]);
        $userEndpoint->allowedIncludes(['servers' => $serverEndpoint]);

        $query = $this->request($userEndpoint, ['include' => 'servers.user.servers']);

        $this->assertArrayHasKey('servers', $query->getEagerLoads());
        $this->assertArrayHasKey('servers.user', $query->getEagerLoads());
        $this->assertArrayHasKey('servers.user.servers', $query->getEagerLoads());

        Endpoint::$maxEndpointDepth = 2;

        try {
            $this->request($userEndpoint, ['include' => 'servers.user.servers']);
        } catch (InvalidIncludeQuery $e) {
            Endpoint::$maxEndpointDepth = 5;

            return;
        }
        $this->assertFalse(true); // Should not get to this point
    }

    /** @test **/
    public function it_merges_relational_append_constraints_into_include_constraints()
    {
        $invoked = [];

        $endpoint = Endpoint::for(User::class)
            ->allowedIncludes(['servers' => function () use (&$invoked) {
                $invoked['includes'] = true;
            }])
            ->allowedAppends(['servers.active_servers' => function () use (&$invoked) {
                $invoked['appends'] = true;
            }]);

        $this->request($endpoint, [
            'include' => 'servers',
            'append' => 'servers.active_servers',
        ]);

        $this->assertArrayHasKey('appends', $invoked);
        $this->assertArrayHasKey('includes', $invoked);
    }

    /** @test **/
    public function it_only_applies_relational_appends_when_relation_is_included()
    {
        $invoked = [];

        $endpoint = Endpoint::for(User::class)
            ->allowedIncludes(['servers' => function () use (&$invoked) {
                $invoked['includes'] = true;
            }])
            ->allowedAppends(['servers.active_servers' => function () use (&$invoked) {
                $invoked['appends'] = true;
            }]);

        $this->request($endpoint, [
            'append' => 'servers.active_servers',
        ]);

        $this->assertEquals([], $invoked);
    }

    /** @test **/
    public function it_only_applies_endpoint_append_constraints_when_appended()
    {
        $endpoint = Endpoint::for(User::class)
            ->allowedIncludes([
                'teams', // Regular relation
                'servers' => Endpoint::for(Server::class)->allowedAppends([ // Endpoint
                    'is_active' => $this->invokable('Unexpected apply of server.is_active'),
                ]),
            ])
            ->allowedAppends([ // Root resource
                'servers_count' => $this->invokable('Unexpected apply of servers_count'),
                'servers.active_servers' => $this->invokable('Unexpected apply of servers.active_servers'),
            ]);

        $this->request($endpoint, ['include' => 'servers,teams']);
        $this->assertTrue(true); // If reached this point without exceptions, we've succeeded
    }

    /** @test **/
    public function any_append_may_have_a_custom_constraint_defined()
    {
        $endpoint = Endpoint::for(User::class)->allowedAppends([
            'full_name' => $this->invokable(),
        ]);

        $this->request($endpoint, ['append' => '']); // Should not invoke constraint

        $this->expectInvoked();
        $this->request($endpoint, ['append' => 'full_name']);
    }

    /** @test **/
    public function it_applies_endpoint_taps_when_relation_is_included()
    {
        $endpoint = Endpoint::for(User::class)
            ->allowedIncludes([
                'servers' => Endpoint::for(Server::class)->tap($this->invokable()),
            ]);

        $this->request($endpoint); // not invoked

        $this->expectInvoked();
        $this->request($endpoint, ['include' => 'servers']);
    }

    /** @test **/
    public function regression_it_invokes_when_including_on_snake_case()
    {
        $endpoint = Endpoint::for(User::class)
            ->allowedIncludes(['servers', 'servers_count'])
            ->whenIncluding('servers_count', $this->invokable());

        $this->request($endpoint, ['include' => 'servers']); // Should not invoke constraint

        $this->expectInvoked();
        $this->request($endpoint, ['include' => 'servers_count']);
    }

    // _________________________________________________________________________________________________________________

    protected function invokable($message = null)
    {
        return function () use ($message) {
            throw new \Exception($message ?? 'invoked');
        };
    }

    protected function expectInvoked()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('invoked');
    }

    protected function request(Endpoint $endpoint, $data = [])
    {
        $query = $endpoint
            ->toQueryBuilder(Request::create('foo', 'GET', $data))
            ->applyQueuedConstraints();

        // Instead of actually running eager-loads against the database
        // we will just mock it by invoking each relational constraint.
        foreach ($query->getEagerLoads() as $constraint) {
            $constraint($query);
        }

        return $query;
    }
}
