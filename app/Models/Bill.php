<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Database\Factories\BillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bill extends Model
{
    /** @use HasFactory<BillFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'transaction_id',
        'bill_number',
        'amount',
        'status',
        'notes',
        'issued_at',
        'due_at',
        'paid_at',
    ];

    protected $casts = [
        'id' => 'string',
        'amount' => 'integer',
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
