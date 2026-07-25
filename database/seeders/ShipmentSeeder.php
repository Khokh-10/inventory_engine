<?php

namespace Database\Seeders;

use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Seeder;

class ShipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = Order::query()->whereIn('status', [
            'picked',
            'partially_shipped',
            'shipped',
        ])->get();

        foreach ($orders as $order) {
            Shipment::factory()->create([
                'order_id' => $order->id,
                'tracking_number' => fake()->optional()->bothify('TRK-#######'),
                'status' => fake()->randomElement([
                    ShipmentStatus::PENDING->value,
                    ShipmentStatus::PACKED->value,
                    ShipmentStatus::IN_TRANSIT->value,
                    ShipmentStatus::DELIVERED->value,
                    ShipmentStatus::FAILED->value,
                ]),
            ]);
        }
    }
}
