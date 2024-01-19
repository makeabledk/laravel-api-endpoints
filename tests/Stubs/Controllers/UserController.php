<?php

namespace Makeable\ApiEndpoints\Tests\Stubs\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Makeable\ApiEndpoints\Tests\Stubs\Endpoints\UserEndpoint;

use function response;

class UserController extends Controller
{
    public function index(UserEndpoint $endpoint): JsonResponse
    {
        return response()->json($endpoint->get());
    }
}
