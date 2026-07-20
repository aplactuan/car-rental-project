<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    public const TYPE_PERSONAL = 'personal';

    public const TYPE_BUSINESS = 'business';

    /** @use HasFactory<CustomerFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'type',
        'parent_id',
    ];

    protected $casts = [
        'id' => 'string',
        'parent_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function isSubAccount(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Whether the given customer id appears in this customer's descendant tree.
     */
    public function hasDescendant(string $customerId): bool
    {
        $children = $this->children()->get(['id']);

        foreach ($children as $child) {
            if ($child->id === $customerId || $child->hasDescendant($customerId)) {
                return true;
            }
        }

        return false;
    }
}
