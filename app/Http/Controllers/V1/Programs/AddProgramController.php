<?php

namespace App\Http\Controllers\V1\Programs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Program\AddProgramRequest;
use App\Http\Resources\V1\ProgramResource;
use App\Repositories\Contracts\ProgramRepositoryInterface;

class AddProgramController extends Controller
{
    public function __construct(protected ProgramRepositoryInterface $program) {}

    public function __invoke(AddProgramRequest $request)
    {
        $program = $this->program->create($request->validated());

        return (new ProgramResource($program))
            ->response()
            ->setStatusCode(201);
    }
}
