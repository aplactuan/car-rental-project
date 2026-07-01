<?php

namespace App\Jobs;

use App\Enums\DriverImportStatus;
use App\Enums\UserRole;
use App\Models\DriverImport;
use App\Models\User;
use App\Repositories\Contracts\DriverRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ImportDriversJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public DriverImport $driverImport) {}

    public function handle(DriverRepositoryInterface $driverRepository): void
    {
        $this->driverImport->update(['status' => DriverImportStatus::Processing]);

        $path = Storage::path($this->driverImport->file_path);
        $file = new \SplFileObject($path, 'r');
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);

        $headers = null;
        $rowNumber = 0;
        $importedCount = 0;
        $failures = [];
        $seenLicenseNumbers = [];
        $seenEmails = [];

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
                'first_name' => ['required', 'string'],
                'last_name' => ['required', 'string'],
                'license_number' => ['required', 'string', 'unique:drivers,license_number'],
                'license_expiry_date' => ['required', 'date'],
                'address' => ['required', 'string'],
                'phone_number' => ['required', 'string'],
                'email' => ['required', 'string', 'email', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            $licenseNumber = $data['license_number'] ?? null;
            $email = isset($data['email']) ? mb_strtolower(trim($data['email'])) : null;

            if (isset($seenLicenseNumbers[$licenseNumber])) {
                $validator->errors()->add('license_number', 'The license number is duplicated within the CSV.');
            }

            if (isset($seenEmails[$email])) {
                $validator->errors()->add('email', 'The email is duplicated within the CSV.');
            }

            if ($validator->fails() || isset($seenLicenseNumbers[$licenseNumber]) || isset($seenEmails[$email])) {
                $failures[] = [
                    'row' => $rowNumber,
                    'errors' => $validator->errors()->toArray(),
                ];

                continue;
            }

            $validated = $validator->validated();

            $user = User::create([
                'name' => $validated['first_name'].' '.$validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => UserRole::User,
            ]);

            $driverRepository->create([
                ...$validated,
                'user_id' => $user->id,
            ]);
            $seenLicenseNumbers[$licenseNumber] = true;
            $seenEmails[$email] = true;
            $importedCount++;
        }

        $this->driverImport->update([
            'status' => DriverImportStatus::Completed,
            'total_rows' => $rowNumber,
            'imported_count' => $importedCount,
            'failed_count' => count($failures),
            'failures' => empty($failures) ? null : $failures,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $this->driverImport->update(['status' => DriverImportStatus::Failed]);
    }
}
