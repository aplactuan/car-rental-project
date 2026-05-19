<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Database\Factories\CarFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Car extends Model
{
    /** @use HasFactory<CarFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'type', 'door', 'seats', 'year', 'color', 'make', 'model', 'plate_number',
    ];

    protected $casts = [
        'id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function schedules(): MorphMany
    {
        return $this->morphMany(Schedule::class, 'scheduleable');
    }
}
