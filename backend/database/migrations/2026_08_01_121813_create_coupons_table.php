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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();

            // Discount Type & Value
            $table->enum('discount_type', ['percentage', 'fixed', 'bogo', 'free_shipping'])->default('percentage');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('max_discount_amount', 10, 2)->nullable();

            // Eligibility
            $table->enum('applies_to', ['all', 'specific_products', 'specific_categories', 'specific_vendors'])->default('all');
            $table->json('eligible_items')->nullable();

            // Minimum Requirements
            $table->decimal('minimum_order_amount', 10, 2)->default(0);
            $table->integer('minimum_quantity')->default(0);

            // Usage Limits
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_limit_per_customer')->default(1);
            $table->integer('used_count')->default(0);

            // Date Range
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Customer Eligibility
            $table->boolean('is_first_time_only')->default(false);
            $table->json('eligible_customer_types')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);

            // Source
            $table->enum('created_by', ['admin', 'vendor'])->default('admin');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('code');
            $table->index('is_active');
            $table->index(['starts_at', 'expires_at']);
            $table->index('created_by');
            $table->index('vendor_id');
            $table->index('applies_to');

            // Foreign Keys
            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
