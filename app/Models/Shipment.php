<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'tracking_number',
        'status',
        'failure_reason',
        'delivered_at',
        'retry_count',
        'last_retry_at',
        'provider_reference',
        'provider_name',
        'provider_response',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ShipmentStatus::class,
        'delivered_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    /**
     * A shipment belongs to an order.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * A shipment may have many items.
     *
     * @return HasMany<ShipmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    /**
     * A shipment may have many webhooks.
     *
     * @return HasMany<ShipmentWebhook, $this>
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(ShipmentWebhook::class);
    }

    /**
     * A shipment may have many history entries.
     *
     * @return HasMany<ShipmentHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(ShipmentHistory::class);
    }
}
