<?php

namespace App\Jobs;

use App\Models\Order;
use App\Support\ReportCacheVersion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessOrderPaymentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $orderId,
    ) {}

    public function handle(): void
    {
        $order = Order::query()->find($this->orderId);

        if ($order === null || $order->payment_status !== Order::PAYMENT_PENDING) {
            return;
        }

        $isSuccess = random_int(0, 1) === 1;

        $order->update([
            'payment_status' => $isSuccess ? Order::PAYMENT_PAID : Order::PAYMENT_FAILED,
            'status' => $isSuccess ? Order::STATUS_COMPLETED : Order::STATUS_CANCELLED,
        ]);

        ReportCacheVersion::bumpOrdersSummaryVersion();
    }
}
