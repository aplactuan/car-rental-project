<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Database\Factories\TripReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripReport extends Model
{
    /** @use HasFactory<TripReportFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'booking_id',
        'report_date',
        'po_number',
        'time_in',
        'time_out',
        'rate',
        'odometer_in',
        'odometer_out',
        'fuel_liters',
        'fuel_amount',
        'invoice_or_or_number',
        'collection_amount',
        'percentage',
        'destinations',
        'driver_id_snapshot',
        'driver_name_snapshot',
        'car_id_snapshot',
        'car_make_snapshot',
        'car_model_snapshot',
        'car_plate_number_snapshot',
        'customer_id_snapshot',
        'customer_name_snapshot',
        'transaction_name_snapshot',
    ];

    protected $casts = [
        'id' => 'string',
        'booking_id' => 'string',
        'report_date' => 'date',
        'rate' => 'integer',
        'odometer_in' => 'integer',
        'odometer_out' => 'integer',
        'fuel_liters' => 'decimal:2',
        'fuel_amount' => 'integer',
        'collection_amount' => 'integer',
        'percentage' => 'decimal:2',
        'destinations' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
