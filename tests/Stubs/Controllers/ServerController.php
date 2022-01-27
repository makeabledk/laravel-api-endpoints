<?php

namespace Makeable\ApiEndpoints\Tests\Stubs\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Makeable\ApiEndpoints\Tests\Stubs\Endpoints\ServerEndpoint;
use function response;

class ServerController extends Controller
{
    public function index(ServerEndpoint $endpoint): JsonResponse
    {
        return response()->json($endpoint->get());
    }
}
