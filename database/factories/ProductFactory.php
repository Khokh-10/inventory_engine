<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-###??')),
            'name' => ucfirst($name),
            'description' => $this->faker->optional(0.8)->sentence(),
        ];
    }
}
