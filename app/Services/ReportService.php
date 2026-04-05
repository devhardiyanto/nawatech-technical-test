<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\ReportCacheVersion;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function ordersSummary(array $filters): array
    {
        $normalizedFilters = [
            'from_date' => $filters['from_date'] ?? null,
            'to_date' => $filters['to_date'] ?? null,
        ];

        $version = ReportCacheVersion::currentOrdersSummaryVersion();
        $hash = md5((string) json_encode($normalizedFilters));
        $cacheKey = "report:orders:summary:v{$version}:{$hash}";
        $ttlSeconds = (int) config('report.cache_ttl_seconds', 120);

        return Cache::remember($cacheKey, $ttlSeconds, function () use ($normalizedFilters): array {
            $ordersQuery = Order::query()->where('payment_status', Order::PAYMENT_PAID);
            $this->applyDateFilters($ordersQuery, $normalizedFilters, 'created_at');

            $totalRevenue = (float) (clone $ordersQuery)->sum('total_amount');
            $totalOrders = (int) (clone $ordersQuery)->count();
            $averageOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0.0;

            $topProductsQuery = OrderItem::query()
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->where('orders.payment_status', Order::PAYMENT_PAID)
                ->select([
                    'products.id as product_id',
                    'products.name as product_name',
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                ])
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('total_quantity')
                ->limit(3);

            $this->applyDateFilters($topProductsQuery, $normalizedFilters, 'orders.created_at');

            $topSellingProducts = $topProductsQuery
                ->get()
                ->map(fn ($row): array => [
                    'product_id' => (int) $row->product_id,
                    'product_name' => (string) $row->product_name,
                    'total_quantity' => (int) $row->total_quantity,
                ])
                ->values()
                ->all();

            return [
                'total_revenue' => round($totalRevenue, 2),
                'total_orders' => $totalOrders,
                'average_order_value' => $averageOrderValue,
                'top_3_selling_products' => $topSellingProducts,
            ];
        });
    }

    private function applyDateFilters(
        EloquentBuilder|QueryBuilder $query,
        array $filters,
        string $column,
    ): void {
        if ($filters['from_date'] !== null) {
            $query->whereDate($column, '>=', $filters['from_date']);
        }

        if ($filters['to_date'] !== null) {
            $query->whereDate($column, '<=', $filters['to_date']);
        }
    }
}
