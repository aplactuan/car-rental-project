<?php

namespace App\Jobs;

use App\Enums\CarImportStatus;
use App\Models\CarImport;
use App\Repositories\Contracts\CarRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ImportCarsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public CarImport $carImport) {}

    public function handle(CarRepositoryInterface $carRepository): void
    {
        $this->carImport->update(['status' => CarImportStatus::Processing]);

        $path = Storage::path($this->carImport->file_path);
        $file = new \SplFileObject($path, 'r');
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);

        $headers = null;
        $rowNumber = 0;
        $importedCount = 0;
        $failures = [];
        $seenPlates = [];

        foreach ($file as $row) {
            if ($headers === null) {
                $headers = array_map('trim', $row);

                continue;
            }

            $rowNumber++;

            if (count($row) !== count($headers)) {
                $failures[] = [
                    'row' => $rowNumber,
                    'errors' => ['row' => ['Row column count does not match headers.']],
                ];

                continue;
            }

            $data = array_combine($headers, array_map('trim', $row));

            $validator = Validator::make($data, [
                'make' => ['required', 'string'],
                'model' => ['required', 'string'],
                'plate_number' => ['required', 'string', 'unique:cars,plate_number'],
                'mileage' => ['required', 'integer'],
                'type' => ['required', 'string'],
                'number_of_seats' => ['required', 'integer'],
                'year' => ['nullable', 'integer'],
            ]);

            $plateNumber = $data['plate_number'] ?? null;

            if (isset($seenPlates[$plateNumber])) {
                $validator->errors()->add('plate_number', 'The plate number is duplicated within the CSV.');
            }

            if ($validator->fails() || isset($seenPlates[$plateNumber])) {
                $failures[] = [
                    'row' => $rowNumber,
                    'errors' => $validator->errors()->toArray(),
                ];

                continue;
            }

            $carRepository->create($validator->validated());
            $seenPlates[$plateNumber] = true;
            $importedCount++;
        }

        $this->carImport->update([
            'status' => CarImportStatus::Completed,
            'total_rows' => $rowNumber,
            'imported_count' => $importedCount,
            'failed_count' => count($failures),
            'failures' => empty($failures) ? null : $failures,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $this->carImport->update(['status' => CarImportStatus::Failed]);
    }
}
