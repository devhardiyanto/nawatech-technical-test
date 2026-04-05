<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', [
                Order::STATUS_PENDING,
                Order::STATUS_PROCESSING,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED,
            ])->default(Order::STATUS_PENDING)->index();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('payment_status', [
                Order::PAYMENT_PENDING,
                Order::PAYMENT_PAID,
                Order::PAYMENT_FAILED,
                Order::PAYMENT_REFUNDED,
            ])->default(Order::PAYMENT_PENDING)->index();
            $table->timestamps();

            $table->index(['created_at', 'id'], 'orders_created_at_id_index');
        });

        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_total_amount_non_negative CHECK (total_amount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
