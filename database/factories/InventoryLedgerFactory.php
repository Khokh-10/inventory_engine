<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Inventory;
use App\Models\InventoryLedger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryLedger>
 */
class InventoryLedgerFactory extends Factory
{
    protected $model = InventoryLedger::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'inventory_id' => Inventory::factory(),
            'transaction_type' => $this->faker->randomElement([
                TransactionType::INITIAL_INTAKE->value,
                TransactionType::RESERVE->value,
                TransactionType::RELEASE->value,
                TransactionType::PICK->value,
                TransactionType::SHIP->value,
                TransactionType::RETURN->value,
                TransactionType::TRANSFER_IN->value,
                TransactionType::TRANSFER_OUT->value,
                TransactionType::ADJUSTMENT_IN->value,
                TransactionType::ADJUSTMENT_OUT->value,
            ]),
            'from_state' => $this->faker->randomElement(['available', 'reserved', 'picked', 'shipped', 'returned']),
            'to_state' => $this->faker->randomElement(['available', 'reserved', 'picked', 'shipped', 'returned']),
            'quantity' => $this->faker->numberBetween(1, 50),
            'reference_type' => $this->faker->randomElement(['order', 'reservation', 'shipment', 'transfer', 'adjustment']),
            'reference_id' => $this->faker->uuid(),
            'performed_by' => $this->faker->optional()->userName(),
            'notes' => $this->faker->optional()->sentence(),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
