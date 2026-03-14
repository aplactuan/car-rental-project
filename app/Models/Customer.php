<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    public const TYPE_PERSONAL = 'personal';

    public const TYPE_BUSINESS = 'business';

    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'type',
    ];

    protected $casts = [
        'id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
