<?php

namespace Makeable\ApiEndpoints\Tests\Stubs;

use Illuminate\Routing\Controller;
use Makeable\ApiEndpoints\Tests\Stubs\Requests\ServerRequest;

class ServerController extends Controller
{
    /**
     * @param ServerRequest $serverRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ServerRequest $serverRequest)
    {
        return response()->json($serverRequest->getQuery()->get());
    }
}
