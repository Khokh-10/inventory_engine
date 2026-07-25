<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\Order;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => $this->faker->randomElement([
                ReservationStatus::ACTIVE->value,
                ReservationStatus::RELEASED->value,
                ReservationStatus::EXPIRED->value,
                ReservationStatus::CONSUMED->value,
            ]),
            'expires_at' => $this->faker->optional()->dateTimeBetween('now', '+3 days'),
        ];
    }
}
