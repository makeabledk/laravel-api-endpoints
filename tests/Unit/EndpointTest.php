<?php

namespace Makeable\ApiEndpoints\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Makeable\ApiEndpoints\Endpoint;
use Makeable\ApiEndpoints\Tests\Stubs\Network;
use Makeable\ApiEndpoints\Tests\Stubs\Server;
use Makeable\ApiEndpoints\Tests\Stubs\Website;
use Makeable\ApiEndpoints\Tests\UnitTestCase;

class EndpointTest extends UnitTestCase
{
    use RefreshDatabase;

    /** @test * */
    public function it_accepts_constraints_when_defining_includes()
    {
        dd(Endpoint::for(Server::class));

        $endpoint = Endpoint::for(Server::class)->allowedIncludes([
            'websites' => $this->invokable(),
        ]);

        $this->expectInvoked();
        $this->request($endpoint, ['include' => 'websites']);
    }

    /** @test **/
    public function it_adapts_namespaced_appends_and_includes_when_adding_another_endpoint()
    {
        $endpoint = Endpoint::for(Server::class)
            ->allowedAppends(['servers_count'])
            ->allowedIncludes([
                'websites',
                'network' => Endpoint::for(Network::class)
                    ->allowedAppends(['ports_open'])
                    ->allowedIncludes(['firewalls']),
            ]);

        $query = $this->request($endpoint, [
            'append' => 'servers_count,network.ports_open',
            'include' => 'websites,network.firewalls',
        ]);

        $this->assertArrayHasKey('websites', $query->getEagerLoads());
        $this->assertArrayHasKey('network', $query->getEagerLoads());
        $this->assertArrayHasKey('network.firewalls', $query->getEagerLoads());
    }

    /** @test **/
    public function it_merges_relational_append_constraints_into_include_constraints()
    {
        $invoked = [];

        $endpoint = Endpoint::for(Server::class)
            ->allowedIncludes(['websites' => function () use (&$invoked) {
                $invoked['includes'] = true;
            }])
            ->allowedAppends(['websites.active_websites' => function () use (&$invoked) {
                $invoked['appends'] = true;
            }]);

        $this->request($endpoint, [
            'include' => 'websites',
            'append' => 'websites.active_websites',
        ]);

        $this->assertArrayHasKey('appends', $invoked);
        $this->assertArrayHasKey('includes', $invoked);
    }

    /** @test **/
    public function it_doesnt_apply_relational_appends_unless_relation_is_included()
    {
        $invoked = [];

        $endpoint = Endpoint::for(Server::class)
            ->allowedIncludes(['websites' => function () use (&$invoked) {
                $invoked['includes'] = true;
            }])
            ->allowedAppends(['websites.active_websites' => function () use (&$invoked) {
                $invoked['appends'] = true;
            }]);

        $this->request($endpoint, [
            'append' => 'websites.active_websites',
        ]);

        $this->assertEquals([], $invoked);
    }

    /** @test **/
    public function it_doesnt_apply_append_constraints_unless_appended()
    {
        $endpoint = Endpoint::for(Server::class)
            ->allowedIncludes([
                'websites', // Regular relation
                'network' => Endpoint::for(Network::class) // Endpoint
                ->allowedAppends([
                    'is_active' => $this->invokable('Unexpected apply of network.is_active'),
                ]),
            ])
            ->allowedAppends([ // Root resource
                'ports_open' => $this->invokable('Unexpected apply of ports_open'),
                'websites.active_websites' => $this->invokable('Unexpected apply of websites.active_websites'),
            ]);

        $this->request($endpoint, ['include' => 'websites,network']);
        $this->assertTrue(true); // If reached this point without exceptions, we've succeeded
    }

    /** @test **/
    public function a_constraint_can_be_given_on_a_non_relational_append()
    {
        $endpoint = Endpoint::for(Server::class)->allowedAppends([
            'websites_count' => $this->invokable(),
        ]);

        $this->request($endpoint, ['append' => '']); // Should not invoke constraint

        $this->expectInvoked();
        $this->request($endpoint, ['append' => 'websites_count']);
    }

    /** @test **/
    public function it_applies_root_query_calls_from_nested_endpoints_on_relations()
    {
        $endpoint = Endpoint::for(Server::class)
            ->allowedIncludes([
                'websites' => Endpoint::for(Website::class)->tap($this->invokable()),
            ]);

        $this->expectInvoked();
        $this->request($endpoint, ['include' => 'websites']);
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
