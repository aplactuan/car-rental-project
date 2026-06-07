<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Database\Factories\BillPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BillPayment extends Model implements HasMedia
{
    /** @use HasFactory<BillPaymentFactory> */
    use HasFactory, HasUuid, InteractsWithMedia;

    public const PROOF_MEDIA_COLLECTION = 'proof';

    protected $fillable = [
        'bill_id',
        'amount',
        'method',
        'reference_number',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'id' => 'string',
        'amount' => 'integer',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PROOF_MEDIA_COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
