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
         Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Foreign Keys
            $table->foreignId('vendor_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('set null');

            // Basic Information
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('short_description', 500)->nullable();

            // Inventory & SKU
            $table->string('sku')->unique();
            $table->integer('stock_quantity')->default(0);
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'low_stock', 'backorder'])
                  ->default('in_stock');

            // Pricing
            $table->decimal('price', 12, 2);
            $table->decimal('compare_price', 12, 2)->nullable();
            $table->decimal('cost_per_item', 12, 2)->nullable();

            // Status & Visibility
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('has_variations')->default(false);
            $table->boolean('is_taxable')->default(true);
            $table->decimal('tax_rate', 5, 2)->default(0);

            // Shipping
            $table->decimal('weight', 10, 2)->nullable()->comment('in kg');
            $table->string('dimensions')->nullable()->comment('LxWxH in cm');
            $table->enum('shipping_type', ['physical', 'digital', 'service'])->default('physical');
            $table->boolean('free_shipping')->default(false);

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();

            // Additional
            $table->json('attributes')->nullable(); // For custom attributes
            $table->json('tags')->nullable();
            $table->integer('sold_count')->default(0);
            $table->decimal('average_rating', 5, 2)->default(0);
            $table->integer('review_count')->default(0);

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('vendor_id');
            $table->index('category_id');
            $table->index('slug');
            $table->index('price');
            $table->index(['is_visible', 'is_featured']);
            $table->index('stock_status');
            $table->index(['category_id', 'is_visible', 'price']);

            // Full text index for search
            $table->fullText(['name', 'description', 'short_description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
