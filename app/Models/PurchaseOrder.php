<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'customer_id',
        'po_number',
        'date',
        'amount',
        'request_person',
        'description',
    ];

    protected $casts = [
        'id' => 'string',
        'customer_id' => 'string',
        'date' => 'date',
        'amount' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
