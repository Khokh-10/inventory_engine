<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\ShipmentWebhook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentWebhook>
 */
class ShipmentWebhookFactory extends Factory
{
    protected $model = ShipmentWebhook::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'event_id' => $this->faker->unique()->uuid(),
            'payload' => [
                'event' => $this->faker->randomElement(['shipment.created', 'shipment.updated', 'shipment.delivered']),
                'tracking_number' => $this->faker->bothify('TRK-#######'),
                'status' => $this->faker->randomElement(['pending', 'packed', 'in_transit', 'delivered', 'failed']),
            ],
            'processed_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
