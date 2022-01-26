<?php

namespace Makeable\ApiEndpoints\Tests\Stubs;

use Illuminate\Routing\Controller;
use Makeable\ApiEndpoints\Tests\Stubs\Endpoints\UserEndpoint;

class UserController extends Controller
{
    /**
     * @param  UserEndpoint  $userRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(UserEndpoint $userRequest)
    {
        return response()->json($userRequest->get());
    }
}
