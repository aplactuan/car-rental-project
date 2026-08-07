<?php

namespace App\Support\Media;

use Illuminate\Http\UploadedFile;
use Maestroerror\HeicToJpg;
use RuntimeException;

class ConvertsHeicUploads
{
    /**
     * @var list<string>
     */
    private const HEIC_EXTENSIONS = ['heic', 'heif'];

    /**
     * @var list<string>
     */
    private const HEIC_MIME_TYPES = [
        'image/heic',
        'image/heif',
        'image/heic-sequence',
        'image/heif-sequence',
    ];

    public function prepare(UploadedFile $file): UploadedFile
    {
        if (! $this->isHeicUpload($file)) {
            return $file;
        }

        return $this->convertToJpeg($file);
    }

    public function isHeicUpload(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, self::HEIC_EXTENSIONS, true)) {
            return true;
        }

        $mimeType = strtolower((string) $file->getMimeType());

        if (in_array($mimeType, self::HEIC_MIME_TYPES, true)) {
            return true;
        }

        $path = $file->getRealPath();

        return $path !== false && is_file($path) && HeicToJpg::isHeic($path);
    }

    private function convertToJpeg(UploadedFile $file): UploadedFile
    {
        $sourcePath = $file->getRealPath();

        if ($sourcePath === false || ! is_file($sourcePath)) {
            throw new RuntimeException('Unable to read the uploaded HEIC file.');
        }

        $jpgPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('heic_', true).'.jpg';

        HeicToJpg::convert($sourcePath)->saveAs($jpgPath);

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.jpg';

        return new UploadedFile(
            $jpgPath,
            $originalName,
            'image/jpeg',
            null,
            true
        );
    }
}
