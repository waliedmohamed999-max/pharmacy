<?php

namespace Database\Factories;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $price = $this->faker->randomFloat(2, 20, 500);
        $qty = $this->faker->numberBetween(1, 4);

        return [
            'product_name_snapshot' => 'منتج تجريبي',
            'price' => $price,
            'qty' => $qty,
            'line_total' => $price * $qty,
        ];
    }
}
