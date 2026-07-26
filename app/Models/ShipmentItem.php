<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentItem extends Model
{
    protected $fillable = [
        'shipment_id',
        'reservation_item_id',
        'quantity',
        'shipped_quantity',
        'remaining_quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'shipped_quantity' => 'integer',
        'remaining_quantity' => 'integer',
    ];

    /**
     * A shipment item belongs to a shipment.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * A shipment item is linked back to the reservation item it came from.
     */
    public function reservationItem(): BelongsTo
    {
        return $this->belongsTo(ReservationItem::class);
    }
}