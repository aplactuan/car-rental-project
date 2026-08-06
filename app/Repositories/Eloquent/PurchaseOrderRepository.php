<?php

namespace App\Repositories\Eloquent;

use App\Models\PurchaseOrder;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PurchaseOrderRepository extends BaseRepository implements PurchaseOrderRepositoryInterface
{
    public function __construct(PurchaseOrder $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array{customer_id?: string, program_id?: string}  $filters
     */
    public function paginate(int $perPage = 15, array $filters = [])
    {
        return $this->model->newQuery()
            ->with('media')
            ->when(
                isset($filters['customer_id']),
                fn (Builder $builder) => $builder->where('customer_id', $filters['customer_id'])
            )
            ->when(
                isset($filters['program_id']),
                fn (Builder $builder) => $builder->where('program_id', $filters['program_id'])
            )
            ->latest('date')
            ->paginate($perPage);
    }

    public function find($id)
    {
        return $this->model->with(['customer', 'program', 'media'])->findOrFail($id);
    }

    /**
     * @param  array<int, UploadedFile>  $attachments
     */
    public function create(array $data, array $attachments = [])
    {
        return DB::transaction(function () use ($data, $attachments) {
            $purchaseOrder = $this->model->create($data);

            $this->addAttachments($purchaseOrder, $attachments);

            return $purchaseOrder->fresh(['customer', 'program', 'media']);
        });
    }

    /**
     * @param  array<int, UploadedFile>  $attachments
     * @param  array<int, string>  $removeAttachmentIds
     */
    public function update($id, array $data, array $attachments = [], array $removeAttachmentIds = [])
    {
        $purchaseOrder = $this->find($id);

        return DB::transaction(function () use ($purchaseOrder, $data, $attachments, $removeAttachmentIds) {
            $purchaseOrder->update($data);

            $this->removeAttachments($purchaseOrder, $removeAttachmentIds);
            $this->addAttachments($purchaseOrder, $attachments);

            return $purchaseOrder->fresh(['customer', 'program', 'media']);
        });
    }

    /**
     * @param  array<int, UploadedFile>  $attachments
     */
    private function addAttachments(PurchaseOrder $purchaseOrder, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $purchaseOrder->addMedia($attachment)
                ->toMediaCollection(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION);
        }
    }

    /**
     * @param  array<int, string>  $removeAttachmentIds
     */
    private function removeAttachments(PurchaseOrder $purchaseOrder, array $removeAttachmentIds): void
    {
        if ($removeAttachmentIds === []) {
            return;
        }

        $purchaseOrder->getMedia(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION)
            ->whereIn('uuid', $removeAttachmentIds)
            ->each(fn ($media) => $media->delete());
    }
}
