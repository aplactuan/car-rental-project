<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    /** @use HasFactory<\Database\Factories\DriverFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'first_name',
        'last_name',
        'license_number',
        'license_expiry_date',
        'address',
        'phone_number',
    ];
}
