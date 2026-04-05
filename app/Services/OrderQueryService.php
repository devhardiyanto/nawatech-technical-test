<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\CursorPaginator;

class OrderQueryService
{
    public function paginate(array $filters): CursorPaginator|LengthAwarePaginator
    {
        $query = Order::query()
            ->select(['id', 'user_id', 'status', 'total_amount', 'payment_status', 'created_at'])
            ->with([
                'user:id,name,email',
                'items:id,order_id,product_id,quantity,price',
                'items.product:id,name,price',
            ]);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $query->orderByDesc('created_at')->orderByDesc('id');

        $perPage = (int) ($filters['per_page'] ?? config('orders.pagination.default_per_page', 20));

        if (isset($filters['page'])) {
            return $query->paginate(
                perPage: $perPage,
                page: (int) $filters['page'],
            );
        }

        return $query->cursorPaginate(
            perPage: $perPage,
            cursor: $filters['cursor'] ?? null,
        );
    }
}
