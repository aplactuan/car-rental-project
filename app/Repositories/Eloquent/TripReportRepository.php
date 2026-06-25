<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Models\TripReport;
use App\Repositories\Contracts\TripReportRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TripReportRepository implements TripReportRepositoryInterface
{
    public function __construct(protected TripReport $model) {}

    public function listForBooking(Booking $booking): Collection
    {
        return $this->model->newQuery()
            ->where('booking_id', $booking->id)
            ->orderByDesc('report_date')
            ->orderByDesc('created_at')
            ->get();
    }

    public function createForBooking(Booking $booking, array $data): TripReport
    {
        $booking->loadMissing(['driver', 'car', 'transaction.customer']);

        $tripReport = $this->model->create([
            'booking_id' => $booking->id,
            'report_date' => $data['report_date'],
            'po_number' => $data['po_number'] ?? null,
            'time_in' => $data['time_in'] ?? null,
            'time_out' => $data['time_out'] ?? null,
            'rate' => $data['rate'] ?? null,
            'odometer_in' => $data['odometer_in'] ?? null,
            'odometer_out' => $data['odometer_out'] ?? null,
            'fuel_liters' => $data['fuel_liters'] ?? null,
            'fuel_amount' => $data['fuel_amount'] ?? null,
            'invoice_or_or_number' => $data['invoice_or_or_number'] ?? null,
            'collection_amount' => $data['collection_amount'] ?? null,
            'percentage' => $data['percentage'] ?? null,
            'destinations' => $data['destinations'] ?? [],
            'driver_id_snapshot' => $booking->driver?->id,
            'driver_name_snapshot' => $this->driverName($booking),
            'car_id_snapshot' => $booking->car?->id,
            'car_make_snapshot' => $booking->car?->make,
            'car_model_snapshot' => $booking->car?->model,
            'car_plate_number_snapshot' => $booking->car?->plate_number,
            'customer_id_snapshot' => $booking->transaction?->customer?->id,
            'customer_name_snapshot' => $booking->transaction?->customer?->name,
            'transaction_name_snapshot' => $booking->transaction?->name,
        ]);

        return $tripReport->fresh();
    }

    public function find(string $id): TripReport
    {
        return $this->model->findOrFail($id);
    }

    public function update(TripReport $tripReport, array $data): TripReport
    {
        $tripReport->update($data);

        return $tripReport->fresh();
    }

    public function delete(TripReport $tripReport): void
    {
        $tripReport->delete();
    }

    private function driverName(Booking $booking): ?string
    {
        if (! $booking->driver) {
            return null;
        }

        return trim("{$booking->driver->first_name} {$booking->driver->last_name}");
    }
}
