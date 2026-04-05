<?php

use App\Jobs\ProcessOrderPaymentJob;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('queues payment processing and returns accepted response', function () {
    Queue::fake();

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'payment_status' => Order::PAYMENT_PENDING,
    ]);

    $response = $this->postJson(route('api.orders.pay', ['order' => $order->id]));

    $response
        ->assertStatus(202)
        ->assertJsonPath('code', 'PAYMENT_QUEUED');

    Queue::assertPushed(ProcessOrderPaymentJob::class, function (ProcessOrderPaymentJob $job) use ($order): bool {
        return $job->orderId === $order->id;
    });
});

it('returns conflict when payment has already been processed', function () {
    Queue::fake();

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'payment_status' => Order::PAYMENT_PAID,
    ]);

    $response = $this->postJson(route('api.orders.pay', ['order' => $order->id]));

    $response
        ->assertStatus(409)
        ->assertJsonPath('code', 'PAYMENT_ALREADY_PROCESSED')
        ->assertJsonPath('details.order_id', $order->id)
        ->assertJsonPath('details.payment_status', Order::PAYMENT_PAID);

    Queue::assertNotPushed(ProcessOrderPaymentJob::class);
});
