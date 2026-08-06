<?php

namespace App\Http\Controllers\V1\Programs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Program\ListProgramsRequest;
use App\Http\Resources\V1\ProgramResource;
use App\Repositories\Contracts\ProgramRepositoryInterface;

class ListProgramsController extends Controller
{
    public function __construct(protected ProgramRepositoryInterface $program) {}

    public function __invoke(ListProgramsRequest $request)
    {
        $perPage = $request->integer('per_page', 15);
        $programs = $this->program->paginate($perPage, $request->filters());

        return ProgramResource::collection($programs);
    }
}
