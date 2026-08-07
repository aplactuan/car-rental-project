<?php

namespace App\Support\Media;

use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaUploader
{
    public const IMAGE_MIMES = 'jpg,jpeg,png,webp,heic,heif';

    public const IMAGE_OR_PDF_MIMES = 'jpg,jpeg,png,webp,heic,heif,pdf';

    public const IMAGE_DOCUMENT_OR_PDF_MIMES = 'jpg,jpeg,png,webp,heic,heif,pdf,doc,docx,xls,xlsx';

    public function __construct(protected ConvertsHeicUploads $heicUploads) {}

    public function add(HasMedia $model, UploadedFile $file, string $collection): Media
    {
        return $model->addMedia($this->heicUploads->prepare($file))
            ->toMediaCollection($collection);
    }
}
