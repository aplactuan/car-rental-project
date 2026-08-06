<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'customer_id',
        'name',
        'description',
    ];

    protected $casts = [
        'id' => 'string',
        'customer_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
