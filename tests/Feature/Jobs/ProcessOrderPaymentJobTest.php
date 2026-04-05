<?php

use App\Jobs\ProcessOrderPaymentJob;
use App\Models\Order;
use App\Models\User;
use App\Support\ReportCacheVersion;
use Illuminate\Support\Facades\Cache;

it('processes pending payment and bumps report cache version', function () {
    Cache::flush();

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => Order::STATUS_PENDING,
        'payment_status' => Order::PAYMENT_PENDING,
    ]);

    $beforeVersion = ReportCacheVersion::currentOrdersSummaryVersion();

    (new ProcessOrderPaymentJob($order->id))->handle();

    $order->refresh();

    expect($order->payment_status)->toBeIn([
        Order::PAYMENT_PAID,
        Order::PAYMENT_FAILED,
    ]);

    if ($order->payment_status === Order::PAYMENT_PAID) {
        expect($order->status)->toBe(Order::STATUS_COMPLETED);
    } else {
        expect($order->status)->toBe(Order::STATUS_CANCELLED);
    }

    expect(ReportCacheVersion::currentOrdersSummaryVersion())->toBe($beforeVersion + 1);
});

it('skips already processed payment without bumping report cache version', function () {
    Cache::flush();

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => Order::STATUS_COMPLETED,
        'payment_status' => Order::PAYMENT_PAID,
    ]);

    $beforeVersion = ReportCacheVersion::currentOrdersSummaryVersion();

    (new ProcessOrderPaymentJob($order->id))->handle();

    $order->refresh();

    expect($order->payment_status)->toBe(Order::PAYMENT_PAID)
        ->and($order->status)->toBe(Order::STATUS_COMPLETED)
        ->and(ReportCacheVersion::currentOrdersSummaryVersion())->toBe($beforeVersion);
});
