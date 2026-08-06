<?php

namespace App\Http\Controllers\V1\Programs;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ProgramResource;
use App\Models\Program;
use App\Repositories\Contracts\ProgramRepositoryInterface;
use App\Traits\ApiResponses;

class SingleProgramController extends Controller
{
    use ApiResponses;

    public function __construct(protected ProgramRepositoryInterface $program) {}

    public function __invoke(Program $program)
    {
        $program = $this->program->find($program->id);

        if (! $program) {
            return $this->error('Program not found', 404);
        }

        return new ProgramResource($program);
    }
}
