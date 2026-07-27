<?php

namespace App\Repositories\Eloquent;

use App\Models\PurchaseOrder;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;

class PurchaseOrderRepository extends BaseRepository implements PurchaseOrderRepositoryInterface
{
    public function __construct(PurchaseOrder $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15)
    {
        return $this->model->latest('date')->paginate($perPage);
    }

    public function find($id)
    {
        return $this->model->with('customer')->findOrFail($id);
    }

    public function create(array $data)
    {
        $purchaseOrder = $this->model->create($data);
        $purchaseOrder->load('customer');

        return $purchaseOrder;
    }

    public function update($id, array $data)
    {
        $purchaseOrder = $this->find($id);
        $purchaseOrder->update($data);
        $purchaseOrder->load('customer');

        return $purchaseOrder;
    }
}
