<?php


namespace Makeable\ApiEndpoints\Tests\Stubs;

use Makeable\ApiEndpoints\Tests\Stubs\Requests\UserRequest;
use Illuminate\Routing\Controller;

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
