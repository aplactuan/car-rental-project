<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Database\Factories\TripReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TripReport extends Model implements HasMedia
{
    /** @use HasFactory<TripReportFactory> */
    use HasFactory, HasUuid, InteractsWithMedia;

    public const TRIP_REPORT_IMAGE_MEDIA_COLLECTION = 'trip_report_image';

    protected $fillable = [
        'purchase_order_id',
        'invoice_id',
        'report_date',
        'driver',
        'destinations',
        'amount',
    ];

    protected $casts = [
        'id' => 'string',
        'purchase_order_id' => 'string',
        'invoice_id' => 'string',
        'report_date' => 'date',
        'amount' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::TRIP_REPORT_IMAGE_MEDIA_COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
