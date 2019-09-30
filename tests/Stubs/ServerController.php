<?php


namespace Makeable\ApiEndpoints\Tests\Stubs;

use Makeable\ApiEndpoints\Tests\Stubs\Requests\ServerRequest;
use Makeable\ApiEndpoints\Tests\Stubs\Requests\UserRequest;
use Illuminate\Routing\Controller;

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
