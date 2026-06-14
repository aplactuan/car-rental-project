<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Driver extends Model
{
    /** @use HasFactory<DriverFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'license_number',
        'license_expiry_date',
        'address',
        'phone_number',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'integer',
        'license_expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function schedules(): MorphMany
    {
        return $this->morphMany(Schedule::class, 'scheduleable');
    }
}
