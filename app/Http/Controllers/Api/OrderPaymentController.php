<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\PaymentAlreadyProcessedException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class OrderPaymentController extends Controller
{
    public function store(Order $order, PaymentService $paymentService): JsonResponse
    {
        try {
            $paymentService->queue($order);
        } catch (PaymentAlreadyProcessedException $exception) {
            return ApiResponse::error(
                code: 'PAYMENT_ALREADY_PROCESSED',
                message: $exception->getMessage(),
                details: [
                    'order_id' => $order->id,
                    'payment_status' => $order->payment_status,
                ],
                status: 409,
            );
        }

        $order->refresh()->load([
            'user:id,name,email',
            'items:id,order_id,product_id,quantity,price',
            'items.product:id,name,price',
        ]);

        return ApiResponse::success(
            code: 'PAYMENT_QUEUED',
            message: 'Payment request has been queued for processing.',
            data: (new OrderResource($order))->resolve(),
            status: 202,
        );
    }
}
