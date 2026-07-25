<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CustomerSeeder::class,
            WarehouseSeeder::class,
            ProductSeeder::class,
            InventorySeeder::class,
            OrderSeeder::class,
            ReservationSeeder::class,
            ShipmentSeeder::class,
            InventoryLedgerSeeder::class,
        ]);
    }
}
