<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Database\Factories\BillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Bill extends Model
{
    /** @use HasFactory<BillFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'transaction_id',
        'bill_number',
        'invoice_number',
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

    protected static function booted(): void
    {
        static::creating(function (Bill $bill): void {
            if ($bill->invoice_number === null) {
                $bill->invoice_number = static::generateNextInvoiceNumber();
            }
        });
    }

    public static function generateNextInvoiceNumber(): string
    {
        return DB::transaction(function (): string {
            $period = now()->format('ym');
            $prefix = "INV-{$period}";

            $latest = static::query()
                ->where('invoice_number', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('invoice_number')
                ->value('invoice_number');

            $sequence = $latest !== null
                ? (int) substr($latest, strlen($prefix)) + 1
                : 1;

            return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
        });
    }
}
