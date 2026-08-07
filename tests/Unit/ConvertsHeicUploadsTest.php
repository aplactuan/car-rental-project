<?php

use App\Support\Media\ConvertsHeicUploads;
use Illuminate\Http\UploadedFile;

it('leaves non-heic uploads unchanged', function () {
    $converter = new ConvertsHeicUploads;
    $original = UploadedFile::fake()->image('receipt.jpg');

    $prepared = $converter->prepare($original);

    expect($prepared)->toBe($original)
        ->and($converter->isHeicUpload($original))->toBeFalse();
});

it('detects heic uploads by extension', function () {
    $converter = new ConvertsHeicUploads;
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('fake-heic-', true).'.heic';
    file_put_contents($path, 'not-a-real-heic');

    $upload = new UploadedFile($path, 'photo.heic', 'image/heic', null, true);

    expect($converter->isHeicUpload($upload))->toBeTrue();

    unlink($path);
});

it('detects heif uploads by extension', function () {
    $converter = new ConvertsHeicUploads;
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('fake-heif-', true).'.heif';
    file_put_contents($path, 'not-a-real-heif');

    $upload = new UploadedFile($path, 'photo.heif', 'image/heif', null, true);

    expect($converter->isHeicUpload($upload))->toBeTrue();

    unlink($path);
});
