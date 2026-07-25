<?php

namespace Database\Seeders;

use App\Enums\ReservationStatus;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\ReservationItem;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = Order::query()->whereIn('status', [
            'reserved',
            'picked',
            'partially_picked',
            'partially_shipped',
            'shipped',
            'confirmed',
        ])->get();

        foreach ($orders as $order) {
            $reservation = Reservation::factory()->create([
                'order_id' => $order->id,
                'status' => fake()->randomElement([
                    ReservationStatus::ACTIVE->value,
                    ReservationStatus::RELEASED->value,
                    ReservationStatus::EXPIRED->value,
                    ReservationStatus::CONSUMED->value,
                ]),
                'expires_at' => fake()->optional()->dateTimeBetween('now', '+3 days'),
            ]);

            $items = $order->items()->get();
            if ($items->isEmpty()) {
                continue;
            }

            foreach ($items as $item) {
                $inventory = Inventory::query()
                    ->where('product_id', $item->product_id)
                    ->inRandomOrder()
                    ->first();

                if (! $inventory) {
                    continue;
                }

                ReservationItem::factory()->create([
                    'reservation_id' => $reservation->id,
                    'inventory_id' => $inventory->id,
                    'quantity' => min($item->quantity, random_int(1, 3)),
                ]);
            }
        }
    }
}
