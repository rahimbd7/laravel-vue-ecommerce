<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('cart_id')->constrained('carts')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('product_variation_id')->nullable()->constrained('product_variations')->onDelete('set null');

            // Product snapshot (in case product changes)
            $table->string('product_name');
            $table->string('product_sku');
            $table->string('product_variation_name')->nullable();
            $table->json('product_attributes')->nullable();

            // Pricing
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2);

            // Track if item is saved for later
            $table->boolean('is_saved_for_later')->default(false);

            $table->timestamps();

            $table->index('cart_id');
            $table->index('product_id');
            $table->index('is_saved_for_later');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
