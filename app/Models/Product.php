<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sku',
        'name',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [];

    /**
     * A product may appear in many inventories.
     *
     * @return HasMany
     */
    public function inventories(): HasMany
    {
        return $this->hasMany('App\\Models\\Inventory');
    }

    /**
     * A product may appear in many order items.
     *
     * @return HasMany
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany('App\\Models\\OrderItem');
    }
}
