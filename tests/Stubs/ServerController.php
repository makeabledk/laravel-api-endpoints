<?php

namespace Makeable\ApiEndpoints\Tests\Stubs;

use Illuminate\Routing\Controller;
use Makeable\ApiEndpoints\Tests\Stubs\Endpoints\ServerEndpoint;

class ServerController extends Controller
{
    /**
     * @param ServerEndpoint $serverRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ServerEndpoint $serverRequest)
    {
        return response()->json($serverRequest->get());
    }
}
