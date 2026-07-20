<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\CustomerRepositoryInterface;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15)
    {
        return $this->model->paginate($perPage);
    }

    public function find($id)
    {
        return $this->model->with('parent')->findOrFail($id);
    }

    public function create(array $data)
    {
        $customer = $this->model->create($data);
        $customer->load('parent');

        return $customer;
    }

    public function update($id, array $data)
    {
        $customer = $this->find($id);
        $customer->update($data);
        $customer->load('parent');

        return $customer;
    }
}
