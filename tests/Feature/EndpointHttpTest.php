<?php

namespace Makeable\ApiEndpoints\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Makeable\ApiEndpoints\Tests\Stubs\Server;
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
            ->assertJsonPath('0.id', $user->id)
            ->assertJsonCount(1, '0.servers')
            ->assertJsonCount(1, '0.servers.0.databases');
    }

    /** @test **/
    public function any_allowed_relation_is_also_countable()
    {
        $user = factory(User::class)->with(2, 'servers')->create();

        $this->getJson('/users?include=servers_count')
            ->assertSuccessful()
            ->assertJson([[
                'id' => $user->id,
                'servers_count' => 2,
            ]]);
    }

    /** @test **/
    public function it_accepts_custom_queries_for_appends()
    {
        $user = factory(User::class)->with(1, 'servers')->create();

        $this
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
        $notFavorite = factory(Server::class)->create(['is_favoured' => false]);
        $favorite = factory(Server::class)->create(['is_favoured' => true]);

        $this
            ->getJson('/servers?filter[favoured]=true')
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([[
                'id' => $favorite->id,
                'is_favoured' => true,
            ]]);
    }
}
