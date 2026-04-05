<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderPaymentController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('orders', [OrderController::class, 'index'])->name('api.orders.index');
Route::post('orders', [OrderController::class, 'store'])->name('api.orders.store');
Route::post('orders/{order}/pay', [OrderPaymentController::class, 'store'])->name('api.orders.pay');

Route::get('reports/orders-summary', [ReportController::class, 'summary'])->name('api.reports.orders-summary');
