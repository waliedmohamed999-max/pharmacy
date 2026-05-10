<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'customer_name' => $this->faker->name(),
            'phone' => '01' . $this->faker->numberBetween(100000000, 999999999),
            'city' => $this->faker->city(),
            'address' => $this->faker->address(),
            'notes' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
            'status' => $this->faker->randomElement(['new', 'preparing', 'shipped', 'completed', 'cancelled']),
            'subtotal' => 0,
            'discount' => 0,
            'shipping' => 0,
            'total' => 0,
        ];
    }
}
