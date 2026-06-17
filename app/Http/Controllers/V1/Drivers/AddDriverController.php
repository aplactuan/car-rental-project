<?php

namespace App\Http\Controllers\V1\Drivers;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\AddDriverRequest;
use App\Http\Resources\V1\DriverResource;
use App\Models\User;
use App\Repositories\Contracts\DriverRepositoryInterface;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Hash;

class AddDriverController extends Controller
{
    use ApiResponses;

    public function __construct(protected DriverRepositoryInterface $driver) {}

    public function __invoke(AddDriverRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::User,
        ]);

        $driver = $this->driver->create([
            ...$validated,
            'user_id' => $user->id,
        ]);

        return (new DriverResource($driver))
            ->response()
            ->setStatusCode(201);
    }
}
