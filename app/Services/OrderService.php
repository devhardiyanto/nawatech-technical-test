<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\ReportCacheVersion;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function create(array $payload): Order
    {
        $order = DB::transaction(function () use ($payload): Order {
            $items = collect($payload['items'])
                ->map(fn (array $item): array => [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                ])
                ->groupBy('product_id')
                ->map(function ($group, int|string $productId): array {
                    return [
                        'product_id' => (int) $productId,
                        'quantity' => (int) $group->sum('quantity'),
                    ];
                })
                ->values();

            $productIds = $items->pluck('product_id')->unique()->sort()->values();

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $insufficientStockDetails = [];
            $lineItems = [];
            $totalAmount = 0.0;

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];
                $product = $products->get($productId);

                if ($product === null || $product->stock < $quantity) {
                    $insufficientStockDetails[] = [
                        'product_id' => $productId,
                        'requested_qty' => $quantity,
                        'available_stock' => $product?->stock ?? 0,
                    ];

                    continue;
                }

                $linePrice = round((float) $product->price, 2);

                $lineItems[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $linePrice,
                ];

                $totalAmount += $linePrice * $quantity;
            }

            if ($insufficientStockDetails !== []) {
                throw new InsufficientStockException($insufficientStockDetails);
            }

            $order = Order::query()->create([
                'user_id' => $payload['user_id'],
                'status' => Order::STATUS_PENDING,
                'total_amount' => round($totalAmount, 2),
                'payment_status' => Order::PAYMENT_PENDING,
            ]);

            foreach ($lineItems as $lineItem) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $lineItem['product_id'],
                    'quantity' => $lineItem['quantity'],
                    'price' => $lineItem['price'],
                ]);

                Product::query()
                    ->whereKey($lineItem['product_id'])
                    ->decrement('stock', $lineItem['quantity']);
            }

            ReportCacheVersion::bumpOrdersSummaryVersion();

            return $order;
        }, 3);

        return $order->load([
            'user:id,name,email',
            'items:id,order_id,product_id,quantity,price',
            'items.product:id,name,price',
        ]);
    }
}
