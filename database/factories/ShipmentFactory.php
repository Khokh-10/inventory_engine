<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'tracking_number' => $this->faker->optional()->bothify('TRK-#######'),
            'status' => $this->faker->randomElement([
                ShipmentStatus::PENDING->value,
                ShipmentStatus::PACKED->value,
                ShipmentStatus::IN_TRANSIT->value,
                ShipmentStatus::DELIVERED->value,
                ShipmentStatus::FAILED->value,
            ]),
        ];
    }
}
