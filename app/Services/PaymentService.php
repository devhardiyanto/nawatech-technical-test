<?php

namespace App\Services;

use App\Exceptions\PaymentAlreadyProcessedException;
use App\Jobs\ProcessOrderPaymentJob;
use App\Models\Order;

class PaymentService
{
    public function queue(Order $order): void
    {
        if ($order->payment_status !== Order::PAYMENT_PENDING) {
            throw new PaymentAlreadyProcessedException();
        }

        $connection = config('queue.default') === 'redis'
            ? 'redis'
            : config('queue.default');

        ProcessOrderPaymentJob::dispatch($order->id)
            ->onConnection($connection)
            ->onQueue('payments');
    }
}
