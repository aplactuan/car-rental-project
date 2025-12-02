<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use ApiResponses;
    /**
     * Handle the incoming request.
     */
    public function __invoke(LoginRequest $request)
    {
        return $this->ok('hello');
    }
}
