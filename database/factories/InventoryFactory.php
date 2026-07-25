<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $total = $this->faker->numberBetween(50, 200);

        return [
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'total_quantity' => $total,
            'available_quantity' => $total,
            'reserved_quantity' => 0,
            'picked_quantity' => 0,
            'shipped_quantity' => 0,
            'version' => 1,
        ];
    }
}
