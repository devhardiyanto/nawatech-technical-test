<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->index(['payment_status', 'created_at', 'id'], 'orders_pay_created_id_idx');
            $table->index(['status', 'created_at', 'id'], 'orders_status_created_id_idx');
            $table->index(['user_id', 'created_at', 'id'], 'orders_user_created_id_idx');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->index(['product_id', 'order_id'], 'order_items_product_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_pay_created_id_idx');
            $table->dropIndex('orders_status_created_id_idx');
            $table->dropIndex('orders_user_created_id_idx');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropIndex('order_items_product_order_idx');
        });
    }
};
