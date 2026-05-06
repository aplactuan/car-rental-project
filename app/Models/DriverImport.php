<?php

namespace App\Models;

use App\Enums\DriverImportStatus;
use App\Models\Traits\HasUuid;
use Database\Factories\DriverImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverImport extends Model
{
    /** @use HasFactory<DriverImportFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'status',
        'file_path',
        'total_rows',
        'imported_count',
        'failed_count',
        'failures',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'status' => DriverImportStatus::class,
            'failures' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
