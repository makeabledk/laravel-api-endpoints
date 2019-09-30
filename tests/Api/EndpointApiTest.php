<?php


namespace Makeable\ApiEndpoints\Tests\Api;

use Illuminate\Routing\Router;
use Makeable\ApiEndpoints\Tests\TestCase;
use Makeable\ApiEndpoints\Tests\Stubs\User;
use Makeable\ApiEndpoints\Tests\Stubs\Server;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EndpointApiTest extends TestCase
{
    use RefreshDatabase;


    /** @test */
    public function it_can_load_user_with_nested_relations()
    {
        $this
            ->set($user = factory(User::class)->create())
            ->set($server = factory(Server::class)
                ->with(3, 'databases')
                ->create(['owner_id' => $user->id])
            )
            ->set($server->users()->attach($user))
            ->getJson('/users?include=servers.databases&append=servers.databases_count')
            ->assertSuccessful()
            ->tap(function (TestResponse $response) {
                $data = json_decode($response->content(), true);

                $this->assertArrayHasKey('servers', data_get($data, '0'));
                $this->assertEquals(3, count(data_get($data, '0.servers.0.databases')));
                $this->assertEquals(3, data_get($data, '0.servers.0.databases_count'));
            });
    }

    /** @test */
    public function it_can_filter_favoured_servers()
    {

        $this
            ->set($user = factory(User::class)->create())
            ->set($servers = factory(Server::class)
                ->times(3)
                ->create(['owner_id' => $user->id])
            )
            ->set($server = factory(Server::class)
                ->create([
                    'owner_id' => $user->id,
                    'is_favoured' => true
                ])
            )
            ->set($servers->push($server))
            ->set($servers->each(function ($server) use ($user) {
                $server->users()->attach($user);
            }))
            ->getJson('/servers?filter[favoured]=true')
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->tap(function (TestResponse $response) {
                $data = json_decode($response->content(), true);

                $this->assertEquals(true, data_get($data, '0.is_favoured'));
            });
    }
}
