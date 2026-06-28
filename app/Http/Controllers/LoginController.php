<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use ApiResponses;

    /**
     * Handle the incoming request.
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $request->validated($request->all());

        if (! Auth::attempt($request->only('email', 'password'))) {
            return $this->error('Invalid username or password', 401);
        }

        /** @var User $user */
        $user = Auth::user()->loadMissing('driver');

        return $this->ok(
            'Authenticated',
            [
                'token' => $user->createToken('API token for '.$user->email)->plainTextToken,
                'role' => $user->apiRole(),
                'user' => new UserResource($user),
            ]
        );
    }
}
