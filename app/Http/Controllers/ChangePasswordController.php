<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;

class ChangePasswordController extends Controller
{
    use ApiResponses;

    public function __invoke(ChangePasswordRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        return $this->ok('Password updated successfully');
    }
}
