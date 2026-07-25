<?php

namespace App\Models;

use App\Enums\InventoryState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'total_quantity',
        'available_quantity',
        'reserved_quantity',
        'picked_quantity',
        'shipped_quantity',
        'version',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'available_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'picked_quantity' => 'integer',
        'shipped_quantity' => 'integer',
        'total_quantity' => 'integer',
        'version' => 'integer',
    ];

    /**
     * Inventory belongs to a warehouse.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Inventory belongs to a product.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Inventory may have many reservation items.
     *
     * @return HasMany
     */
    public function reservationItems(): HasMany
    {
        return $this->hasMany('App\\Models\\ReservationItem');
    }

    /**
     * Inventory may have many ledger entries.
     *
     * @return HasMany
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany('App\\Models\\InventoryLedger');
    }

    /**
     * Determine the current state of the inventory snapshot.
     */
    public function currentState(): InventoryState
    {
        if ($this->available_quantity > 0) {
            return InventoryState::AVAILABLE;
        }

        if ($this->reserved_quantity > 0) {
            return InventoryState::RESERVED;
        }

        if ($this->picked_quantity > 0) {
            return InventoryState::PICKED;
        }

        if ($this->shipped_quantity > 0) {
            return InventoryState::SHIPPED;
        }

        return InventoryState::AVAILABLE;
    }
}
