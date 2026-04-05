<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

it('creates an order and decrements stock atomically for valid stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'price' => 50,
        'stock' => 10,
    ]);

    $response = $this->postJson(route('api.orders.store'), [
        'user_id' => $user->id,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 3,
            ],
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('code', 'ORDER_CREATED')
        ->assertJsonPath('data.total_amount', '150.00');

    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'status' => Order::STATUS_PENDING,
        'payment_status' => Order::PAYMENT_PENDING,
    ]);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'stock' => 7,
    ]);
});

it('returns insufficient stock response when requested quantity exceeds available stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'stock' => 2,
    ]);

    $response = $this->postJson(route('api.orders.store'), [
        'user_id' => $user->id,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 5,
            ],
        ],
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('code', 'INSUFFICIENT_STOCK')
        ->assertJsonPath('details.0.product_id', $product->id)
        ->assertJsonPath('details.0.requested_qty', 5)
        ->assertJsonPath('details.0.available_stock', 2);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'stock' => 2,
    ]);
});

it('aggregates duplicate product lines before stock validation and decrement', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'price' => 20,
        'stock' => 8,
    ]);

    $response = $this->postJson(route('api.orders.store'), [
        'user_id' => $user->id,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 3,
            ],
            [
                'product_id' => $product->id,
                'quantity' => 4,
            ],
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('code', 'ORDER_CREATED')
        ->assertJsonPath('data.total_amount', '140.00')
        ->assertJsonPath('data.items.0.quantity', 7);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'stock' => 1,
    ]);
});
