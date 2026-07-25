<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::query()->get();
        $products = Product::query()->get();

        for ($i = 0; $i < 15; $i++) {
            $customer = $customers->random();
            $order = Order::factory()->create([
                'customer_id' => $customer->id,
                'status' => fake()->randomElement([
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
            ]);

            $itemCount = random_int(1, 5);
            $total = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products->random();
                $quantity = random_int(1, 5);
                $unitPrice = round(fake()->randomFloat(2, 10, 300), 2);
                $subtotal = round($quantity * $unitPrice, 2);

                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                $total += $subtotal;
            }

            $order->update(['total' => round($total, 2)]);
        }
    }
}
