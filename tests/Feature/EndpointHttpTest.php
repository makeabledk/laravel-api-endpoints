<?php

namespace Makeable\ApiEndpoints\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Makeable\ApiEndpoints\Tests\Stubs\Server;
use Makeable\ApiEndpoints\Tests\Stubs\Team;
use Makeable\ApiEndpoints\Tests\Stubs\User;
use Makeable\ApiEndpoints\Tests\TestCase;

class EndpointHttpTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_load_model_with_nested_endpoint_relations()
    {
        $user = factory(User::class)
            ->with(1, 'servers.databases')
            ->create();

        $this
            ->withoutExceptionHandling()
            ->getJson('/users?include=servers.databases')
            ->assertSuccessful()
            ->assertJsonCount(1, '0.servers')
            ->assertJsonCount(1, '0.servers.0.databases')
            ->assertJson([[
                'id' => $user->id,
            ]]);
    }

    /** @test **/
    public function any_allowed_relation_is_also_countable()
    {
        $user = factory(User::class)->with(2, 'servers')->create();

        $this
            ->withoutExceptionHandling()
            ->getJson('/users?include=serversCount')
            ->assertSuccessful()
            ->assertJson([[
                'id' => $user->id,
                'servers_count' => 2,
            ]]);
    }

    /** @test **/
    public function it_appends_attributes()
    {
        $server = factory(Server::class)->create();

        $this
            ->withoutExceptionHandling()
            ->getJson('/servers?append=internal_ip')
            ->assertSuccessful()
            ->assertJson([[
                'id' => $server->id,
                'internal_ip' => '127.0.0.1',
            ]]);
    }

    /** @test **/
    public function it_accepts_custom_queries_for_appends()
    {
        $user = factory(User::class)->with(1, 'servers')->create();

        $this
            ->withoutExceptionHandling()
            ->getJson('/users?include=servers&append=servers.status')
            ->assertSuccessful()
            ->assertJson([[
                'id' => $user->id,
                'servers' => [[
                    'status' => 'active',
                ]],
            ]]);
    }

    /** @test */
    public function filters_may_be_applied()
    {
        $notFavorite = factory(Server::class)->create(['is_favorite' => false]);
        $favorite = factory(Server::class)->create(['is_favorite' => true]);

        $this
            ->getJson('/servers?filter[favorite]=true')
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([[
                'id' => $favorite->id,
                'is_favorite' => true,
            ]]);
    }

    /** @test **/
    public function it_normalizes_snake_case_to_camel_case()
    {
        factory(Team::class)
            ->with(1, 'users')
            ->with(1, 'users.servers', 'favorite')
            ->with(1, 'users.servers.databases')
            ->create();

        $this
            ->withoutExceptionHandling()
            ->getJson('/teams?include=users.favorite_servers.databases')
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJsonCount(1, '0.users.0.favorite_servers')
            ->assertJsonCount(1, '0.users.0.favorite_servers.0.databases');
    }
}
