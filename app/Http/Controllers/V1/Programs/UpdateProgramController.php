<?php

namespace App\Http\Controllers\V1\Programs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Program\UpdateProgramRequest;
use App\Http\Resources\V1\ProgramResource;
use App\Models\Program;
use App\Repositories\Contracts\ProgramRepositoryInterface;
use App\Traits\ApiResponses;

class UpdateProgramController extends Controller
{
    use ApiResponses;

    public function __construct(protected ProgramRepositoryInterface $program) {}

    public function __invoke(Program $program, UpdateProgramRequest $request)
    {
        $program = $this->program->find($program->id);

        if (! $program) {
            return $this->error('Program not found', 404);
        }

        $program = $this->program->update($program->id, $request->validated());

        return new ProgramResource($program);
    }
}
