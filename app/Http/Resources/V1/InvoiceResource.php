<?php

namespace App\Http\Resources\V1;

use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Bill */
class InvoiceResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'invoiceNumber' => $this->invoice_number,
            'billNumber' => $this->bill_number,
            'status' => $this->status,
            'issuedAt' => $this->issued_at?->toIso8601String(),
            'dueAt' => $this->due_at?->toIso8601String(),
            'paidAt' => $this->paid_at?->toIso8601String(),
            'amount' => $this->amount,
            'notes' => $this->notes,
            'transaction' => [
                'name' => $this->transaction->name,
            ],
            'customer' => $this->transaction->customer ? [
                'name' => $this->transaction->customer->name,
                'type' => $this->transaction->customer->type,
            ] : null,
            'bookings' => $this->transaction->bookings->map(fn ($booking) => [
                'startDate' => $booking->start_date?->format('Y-m-d H:i:s'),
                'endDate' => $booking->end_date?->format('Y-m-d H:i:s'),
                'price' => $booking->price,
                'note' => $booking->note,
                'car' => $booking->car ? [
                    'make' => $booking->car->make,
                    'model' => $booking->car->model,
                    'plateNumber' => $booking->car->plate_number,
                ] : null,
                'driver' => $booking->driver ? [
                    'firstName' => $booking->driver->first_name,
                    'lastName' => $booking->driver->last_name,
                ] : null,
            ])->values()->all(),
        ];
    }
}
