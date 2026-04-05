<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => fake()->randomElement([
                Order::STATUS_PENDING,
                Order::STATUS_PROCESSING,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED,
            ]),
            'total_amount' => fake()->randomFloat(2, 10, 10000),
            'payment_status' => fake()->randomElement([
                Order::PAYMENT_PENDING,
                Order::PAYMENT_PAID,
                Order::PAYMENT_FAILED,
                Order::PAYMENT_REFUNDED,
            ]),
        ];
    }
}
