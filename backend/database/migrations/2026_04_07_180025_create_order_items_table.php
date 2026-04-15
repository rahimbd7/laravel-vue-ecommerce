<?php
// database/migrations/2026_03_27_000002_create_order_items_table.php

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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Foreign Keys
            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->onDelete('cascade');

            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('restrict');

            $table->foreignId('product_variation_id')
                  ->nullable()
                  ->constrained('product_variations')
                  ->onDelete('set null');

            // Product Information (snapshot at time of order)
            $table->string('product_name');
            $table->string('product_sku');
            $table->string('product_variation_name')->nullable();
            $table->json('product_attributes')->nullable();

            // Pricing
            $table->decimal('unit_price', 12, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2);

            // Status
            $table->enum('status', [
                'pending',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
                'refunded'
            ])->default('pending');

            // Tracking for individual items
            $table->string('tracking_number')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('order_id');
            $table->index('product_id');
            $table->index('status');
            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
