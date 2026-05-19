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
            if (! is_array($row)) {
                continue;
            }

            $row = array_map(
                static fn (mixed $value): string => is_string($value) ? trim($value) : '',
                $row
            );

            if ($row === [''] || count(array_filter($row, static fn (string $value): bool => $value !== '')) === 0) {
                continue;
            }

            if ($headers === null) {
                $headers = $row;

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

            $data = array_combine($headers, $row);

            $validator = Validator::make($data, [
                'type' => ['required', 'string'],
                'door' => ['required', 'integer'],
                'seats' => ['required', 'integer'],
                'year' => ['required', 'integer'],
                'color' => ['required', 'string'],
                'make' => ['required', 'string'],
                'model' => ['required', 'string'],
                'plate_number' => ['required', 'string', 'unique:cars,plate_number'],
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
