<?php

namespace App\Models;

use App\Enums\CarImportStatus;
use App\Models\Traits\HasUuid;
use Database\Factories\CarImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarImport extends Model
{
    /** @use HasFactory<CarImportFactory> */
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
            'status' => CarImportStatus::class,
            'failures' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
