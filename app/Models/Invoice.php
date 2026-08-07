<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Invoice extends Model implements HasMedia
{
    use HasUuid, InteractsWithMedia;

    public const PAYMENT_RECEIPT_MEDIA_COLLECTION = 'payment_receipt';

    public const DISBURSEMENT_VOUCHER_MEDIA_COLLECTION = 'disbursement_voucher';

    public const INVOICE_PICTURE_MEDIA_COLLECTION = 'invoice_picture';

    protected $fillable = [
        'purchase_order_id',
        'invoice_number',
        'lddap_adap_no',
        'note',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'purchase_order_id' => 'string',
            'status' => InvoiceStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function tripReports(): HasMany
    {
        return $this->hasMany(TripReport::class);
    }

    public function registerMediaCollections(): void
    {
        $documentMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
        ];

        $this->addMediaCollection(self::PAYMENT_RECEIPT_MEDIA_COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes($documentMimeTypes);

        $this->addMediaCollection(self::DISBURSEMENT_VOUCHER_MEDIA_COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes($documentMimeTypes);

        $this->addMediaCollection(self::INVOICE_PICTURE_MEDIA_COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes($documentMimeTypes);
    }
}
