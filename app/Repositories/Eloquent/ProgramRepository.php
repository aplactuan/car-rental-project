<?php

namespace App\Repositories\Eloquent;

use App\Models\Program;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\ProgramRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class ProgramRepository extends BaseRepository implements ProgramRepositoryInterface
{
    public function __construct(Program $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array{customer_id?: string}  $filters
     */
    public function paginate(int $perPage = 15, array $filters = [])
    {
        return $this->model->newQuery()
            ->when(
                isset($filters['customer_id']),
                fn (Builder $builder) => $builder->where('customer_id', $filters['customer_id'])
            )
            ->latest()
            ->paginate($perPage);
    }

    public function find($id)
    {
        return $this->model->with('customer')->findOrFail($id);
    }

    public function create(array $data)
    {
        $program = $this->model->create($data);
        $program->load('customer');

        return $program;
    }

    public function update($id, array $data)
    {
        $program = $this->find($id);
        $program->update($data);
        $program->load('customer');

        return $program;
    }
}
