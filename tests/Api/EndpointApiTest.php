<?php

namespace Makeable\ApiEndpoints\Tests\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestResponse;
use Makeable\ApiEndpoints\Tests\Stubs\Server;
use Makeable\ApiEndpoints\Tests\Stubs\User;
use Makeable\ApiEndpoints\Tests\TestCase;

class EndpointApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_load_user_with_nested_relations()
    {
        $response = $this
            ->set($user = factory(User::class)
                ->with('servers')
                ->with(3, 'servers.databases')
                ->create()
            )
            ->getJson('/users?include=servers.databases&append=servers.databases_count')
            ->assertSuccessful();

        $data = json_decode($response->content(), true);

        $this->assertArrayHasKey('servers', data_get($data, '0'));
        $this->assertEquals(3, count(data_get($data, '0.servers.0.databases')));
        $this->assertEquals(3, data_get($data, '0.servers.0.databases_count'));
    }

    /** @test */
    public function it_can_filter_favoured_servers()
    {
        $response = $this
            ->set($user = factory(User::class)
                ->with(1, 'servers', ['is_favoured' => true])
                ->create()
            )
            ->set(factory(Server::class)
                ->create()
                ->users()
                ->attach($user)
            )
            ->getJson('/servers?filter[favoured]=true')
            ->assertSuccessful()
            ->assertJsonCount(1);

        $data = json_decode($response->content(), true);

        $this->assertEquals(true, data_get($data, '0.is_favoured'));
    }
}
