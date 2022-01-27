<?php

namespace Makeable\ApiEndpoints\Tests\Stubs\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Makeable\ApiEndpoints\Tests\Stubs\Endpoints\TeamEndpoint;
use function response;

class TeamController extends Controller
{
    public function index(TeamEndpoint $endpoint): JsonResponse
    {
        return response()->json($endpoint->get());
    }
}
