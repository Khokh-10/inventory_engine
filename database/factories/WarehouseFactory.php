<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'code' => strtoupper($this->faker->unique()->bothify('WH-###')),
            'location' => $this->faker->city() . ', ' . $this->faker->country(),
        ];
    }
}
