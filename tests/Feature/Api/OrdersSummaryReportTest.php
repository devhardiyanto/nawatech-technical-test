<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\ReportService;
use App\Support\ReportCacheVersion;
use Illuminate\Support\Facades\Cache;

it('returns orders summary report with paid-only revenue metrics', function () {
    $user = User::factory()->create();

    $productA = Product::factory()->create(['name' => 'Product A']);
    $productB = Product::factory()->create(['name' => 'Product B']);

    $paidOrderOne = Order::factory()->create([
        'user_id' => $user->id,
        'total_amount' => 100,
        'payment_status' => Order::PAYMENT_PAID,
    ]);

    $paidOrderTwo = Order::factory()->create([
        'user_id' => $user->id,
        'total_amount' => 300,
        'payment_status' => Order::PAYMENT_PAID,
    ]);

    Order::factory()->create([
        'user_id' => $user->id,
        'total_amount' => 999,
        'payment_status' => Order::PAYMENT_FAILED,
    ]);

    OrderItem::factory()->create([
        'order_id' => $paidOrderOne->id,
        'product_id' => $productA->id,
        'quantity' => 4,
    ]);

    OrderItem::factory()->create([
        'order_id' => $paidOrderTwo->id,
        'product_id' => $productB->id,
        'quantity' => 3,
    ]);

    $response = $this->getJson(route('api.reports.orders-summary'));

    $response
        ->assertOk()
        ->assertJsonPath('code', 'REPORT_ORDERS_SUMMARY_FETCHED')
        ->assertJsonPath('data.total_revenue', 400)
        ->assertJsonPath('data.total_orders', 2)
        ->assertJsonPath('data.average_order_value', 200)
        ->assertJsonPath('meta.cache_ttl_seconds', 120);

    expect($response->json('data.top_3_selling_products'))->toHaveCount(2);
});

it('returns cached summary until report version is bumped', function () {
    Cache::flush();

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'total_amount' => 100,
        'payment_status' => Order::PAYMENT_PAID,
    ]);

    $reportService = app(ReportService::class);

    $firstSummary = $reportService->ordersSummary([]);
    expect($firstSummary['total_revenue'])->toBe(100.0);

    $order->update(['total_amount' => 250]);

    $cachedSummary = $reportService->ordersSummary([]);
    expect($cachedSummary['total_revenue'])->toBe(100.0);

    ReportCacheVersion::bumpOrdersSummaryVersion();

    $freshSummary = $reportService->ordersSummary([]);
    expect($freshSummary['total_revenue'])->toBe(250.0);
});
