<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;

it('returns orders with nested user items and product data', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 99.99]);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => Order::STATUS_COMPLETED,
        'payment_status' => Order::PAYMENT_PAID,
        'total_amount' => 199.98,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 99.99,
    ]);

    $response = $this->getJson(route('api.orders.index'));

    $response
        ->assertOk()
        ->assertJsonPath('code', 'ORDERS_FETCHED')
        ->assertJsonPath('data.0.user.id', $user->id)
        ->assertJsonPath('data.0.items.0.product.id', $product->id);
});

it('clamps per_page to 100 when larger value is requested', function () {
    $user = User::factory()->create();

    Order::factory()->count(120)->create([
        'user_id' => $user->id,
    ]);

    $response = $this->getJson(route('api.orders.index', ['per_page' => 1000]));

    $response
        ->assertOk()
        ->assertJsonPath('meta.pagination.per_page', 100);

    expect($response->json('data'))->toHaveCount(100);
});
