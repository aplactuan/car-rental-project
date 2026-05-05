<?php

namespace App\Http\Controllers\V1\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AddUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;

class AddUserController extends Controller
{
    public function __invoke(AddUserRequest $request)
    {
        $user = User::query()->create($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }
}
