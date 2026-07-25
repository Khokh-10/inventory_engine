<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'status' => $this->faker->randomElement([
                OrderStatus::PENDING->value,
                OrderStatus::CONFIRMED->value,
                OrderStatus::RESERVED->value,
                OrderStatus::PARTIALLY_PICKED->value,
                OrderStatus::PICKED->value,
                OrderStatus::PARTIALLY_SHIPPED->value,
                OrderStatus::SHIPPED->value,
                OrderStatus::RETURNED->value,
                OrderStatus::CANCELLED->value,
            ]),
            'total' => $this->faker->randomFloat(2, 50, 5000),
        ];
    }
}
