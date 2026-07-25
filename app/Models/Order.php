<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'status',
        'total',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => OrderStatus::class,
        'total' => 'decimal:2',
    ];

    /**
     * An order belongs to a customer.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * An order may have many items.
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany('App\\Models\\OrderItem');
    }

    /**
     * An order may have one active reservation.
     *
     * @return HasOne
     */
    public function reservation(): HasOne
    {
        return $this->hasOne('App\\Models\\Reservation');
    }

    /**
     * An order may have one shipment.
     *
     * @return HasOne
     */
    public function shipment(): HasOne
    {
        return $this->hasOne('App\\Models\\Shipment');
    }
}
