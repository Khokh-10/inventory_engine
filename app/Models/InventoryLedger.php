<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLedger extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'inventory_id',
        'transaction_type',
        'from_state',
        'to_state',
        'quantity',
        'reference_type',
        'reference_id',
        'performed_by',
        'notes',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'transaction_type' => TransactionType::class,
        'quantity' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * An inventory ledger entry belongs to an inventory snapshot.
     *
     * @return BelongsTo<Inventory, $this>
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}
