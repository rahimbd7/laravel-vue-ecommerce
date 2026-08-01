<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupon_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id')->nullable();

            // Discount Details
            $table->decimal('original_subtotal', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('discounted_total', 10, 2)->default(0);

            // Context
            $table->json('applied_items')->nullable();
            $table->string('source', 50)->default('checkout');

            // Status
            $table->boolean('is_reversed')->default(false);
            $table->timestamp('used_at')->useCurrent();
            $table->timestamp('reversed_at')->nullable();

            // Indexes
            $table->index('coupon_id');
            $table->index('user_id');
            $table->index('order_id');
            $table->index('used_at');
            $table->index('is_reversed');
            $table->index('source');

            // Foreign Keys
            $table->foreign('coupon_id')
                ->references('id')
                ->on('coupons')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_usage');
    }
};
