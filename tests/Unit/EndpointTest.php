<?php

namespace Makeable\ApiEndpoints\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Makeable\ApiEndpoints\Endpoint;
use Makeable\ApiEndpoints\Tests\Stubs\Server;
use Makeable\ApiEndpoints\Tests\Stubs\User;
use Makeable\ApiEndpoints\Tests\TestCase;

class EndpointTest extends TestCase
{
    use RefreshDatabase;

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
            ->allowedAppends(['servers_count'])
            ->allowedIncludes([
                'teams',
                'servers' => Endpoint::for(Server::class)
                    ->allowedAppends(['databases_count'])
                    ->allowedIncludes(['databases']),
            ]);

        $query = $this->request($endpoint, [
            'append' => 'servers_count,servers.databases_count',
            'include' => 'teams,servers.databases',
        ]);

        $this->assertArrayHasKey('teams', $query->getEagerLoads());
        $this->assertArrayHasKey('servers', $query->getEagerLoads());
        $this->assertArrayHasKey('servers.databases', $query->getEagerLoads());
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
    public function it_doesnt_apply_relational_appends_unless_relation_is_included()
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
    public function it_doesnt_apply_append_constraints_unless_appended()
    {
        $endpoint = Endpoint::for(User::class)
            ->allowedIncludes([
                'teams', // Regular relation
                'servers' => Endpoint::for(Server::class) // Endpoint
                    ->allowedAppends([
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
    public function a_constraint_can_be_given_on_a_non_relational_append()
    {
        $endpoint = Endpoint::for(User::class)->allowedAppends([
            'servers_count' => $this->invokable(),
        ]);

        $this->request($endpoint, ['append' => '']); // Should not invoke constraint

        $this->expectInvoked();
        $this->request($endpoint, ['append' => 'servers_count']);
    }

    /** @test **/
    public function it_applies_root_query_calls_from_nested_endpoints_on_relations()
    {
        $endpoint = Endpoint::for(User::class)
            ->allowedIncludes([
                'servers' => Endpoint::for(Server::class)->tap($this->invokable()),
            ]);

        $this->expectInvoked();
        $this->request($endpoint, ['include' => 'servers']);
    }

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
        return tap($endpoint->toQueryBuilder(Request::create('foo', 'GET', $data)))
            ->eagerLoadRelations([]);
    }
}
