<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Models\Traits\HasUuid;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PurchaseOrder extends Model implements HasMedia
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory, HasUuid, InteractsWithMedia;

    public const ATTACHMENTS_MEDIA_COLLECTION = 'attachments';

    protected $fillable = [
        'customer_id',
        'po_number',
        'date',
        'amount',
        'request_person',
        'description',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'customer_id' => 'string',
        'date' => 'date',
        'amount' => 'integer',
        'status' => PurchaseOrderStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function tripReports(): HasMany
    {
        return $this->hasMany(TripReport::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::ATTACHMENTS_MEDIA_COLLECTION)
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
