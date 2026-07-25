<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Reservation;
use App\Models\ReservationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationItem>
 */
class ReservationItemFactory extends Factory
{
    protected $model = ReservationItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'inventory_id' => Inventory::factory(),
            'quantity' => $this->faker->numberBetween(1, 10),
        ];
    }
}
