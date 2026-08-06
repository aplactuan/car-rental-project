<?php

namespace App\Repositories\Contracts;

use Illuminate\Http\UploadedFile;

interface PurchaseOrderRepositoryInterface
{
    public function all();

    /**
     * @param  array{customer_id?: string, program_id?: string}  $filters
     */
    public function paginate(int $perPage = 15, array $filters = []);

    public function find($id);

    /**
     * @param  array<int, UploadedFile>  $attachments
     */
    public function create(array $data, array $attachments = []);

    /**
     * @param  array<int, UploadedFile>  $attachments
     * @param  array<int, string>  $removeAttachmentIds
     */
    public function update($id, array $data, array $attachments = [], array $removeAttachmentIds = []);

    public function delete($id);
}
