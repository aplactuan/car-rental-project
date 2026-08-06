<?php

namespace App\Http\Controllers\V1\Programs;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Repositories\Contracts\ProgramRepositoryInterface;
use Illuminate\Http\JsonResponse;

class DeleteProgramController extends Controller
{
    public function __construct(protected ProgramRepositoryInterface $program) {}

    public function __invoke(Program $program): JsonResponse
    {
        $this->program->delete($program->id);

        return response()->json(null, 204);
    }
}
