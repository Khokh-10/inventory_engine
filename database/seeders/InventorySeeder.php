<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = Warehouse::query()->get();
        $products = Product::query()->get();

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                $total = random_int(50, 200);

                Inventory::factory()->create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'total_quantity' => $total,
                    'available_quantity' => $total,
                    'reserved_quantity' => 0,
                    'picked_quantity' => 0,
                    'shipped_quantity' => 0,
                    'version' => 1,
                ]);
            }
        }
    }
}
