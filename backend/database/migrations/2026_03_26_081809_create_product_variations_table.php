<?php
// database/migrations/2026_03_26_081809_create_product_variations_table.php

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
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade');

            // Variation Identification
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable();

            // Attributes (JSON for flexible attributes)
            $table->json('attributes')->nullable();

            // Pricing
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('compare_price', 12, 2)->nullable();
            $table->decimal('cost_per_item', 12, 2)->nullable();

            // Inventory
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'low_stock', 'backorder'])
                  ->default('in_stock');

            // Physical attributes
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('dimensions')->nullable();

            // Status
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('position')->default(0);

            // Images
            $table->foreignId('image_id')
                  ->nullable()
                  ->constrained('product_images')
                  ->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('product_id');
            $table->index('sku');
            $table->index('stock_status');
            $table->index(['product_id', 'is_visible']);
            $table->index(['product_id', 'position']);
            $table->index(['product_id', 'is_default']);

            // REMOVED: JSON indexes - they don't work this way in Laravel migrations
            // If you need JSON indexes, add them using DB::statement after table creation

            // Ensure unique combination of product and SKU
            $table->unique(['product_id', 'sku'], 'unique_product_variation_sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variations');
    }
};
