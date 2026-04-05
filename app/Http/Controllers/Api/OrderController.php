<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListOrdersRequest;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderQueryService;
use App\Services\OrderService;
use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\CursorPaginator;

class OrderController extends Controller
{
    public function index(ListOrdersRequest $request, OrderQueryService $orderQueryService): JsonResponse
    {
        $paginator = $orderQueryService->paginate($request->validated());

        $data = OrderResource::collection(collect($paginator->items()))->resolve();

        return ApiResponse::success(
            code: 'ORDERS_FETCHED',
            message: 'Orders retrieved successfully.',
            data: $data,
            meta: [
                'pagination' => $this->buildPaginationMeta($paginator),
            ],
        );
    }

    public function store(StoreOrderRequest $request, OrderService $orderService): JsonResponse
    {
        try {
            $order = $orderService->create($request->validated());

            return ApiResponse::success(
                code: 'ORDER_CREATED',
                message: 'Order created successfully.',
                data: (new OrderResource($order))->resolve(),
                status: 201,
            );
        } catch (InsufficientStockException $exception) {
            return ApiResponse::error(
                code: 'INSUFFICIENT_STOCK',
                message: 'Insufficient stock for one or more products.',
                details: $exception->details(),
                status: 422,
            );
        }
    }

    private function buildPaginationMeta(CursorPaginator|LengthAwarePaginator $paginator): array
    {
        if ($paginator instanceof CursorPaginator) {
            return [
                'type' => 'cursor',
                'per_page' => $paginator->perPage(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
                'has_more_pages' => $paginator->hasMorePages(),
            ];
        }

        return [
            'type' => 'offset',
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ];
    }
}
