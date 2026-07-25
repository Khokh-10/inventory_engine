<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            ['name' => 'Main Warehouse', 'code' => 'MAIN', 'location' => 'Cairo, Egypt'],
            ['name' => 'Alex Warehouse', 'code' => 'ALEX', 'location' => 'Alexandria, Egypt'],
            ['name' => 'Cairo Warehouse', 'code' => 'CAIRO', 'location' => 'Nasr City, Egypt'],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::factory()->create($warehouse);
        }
    }
}
