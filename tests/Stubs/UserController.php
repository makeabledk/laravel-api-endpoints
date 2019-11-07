<?php

namespace Makeable\ApiEndpoints\Tests\Stubs;

use Illuminate\Routing\Controller;
use Makeable\ApiEndpoints\Tests\Stubs\Requests\UserRequest;

class UserController extends Controller
{
    /**
     * @param UserRequest $userRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(UserRequest $userRequest)
    {
        return response()->json($userRequest->getQuery()->get());
    }
}
