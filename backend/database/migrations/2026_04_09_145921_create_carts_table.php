<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // User identification (one of these will be used)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('session_id')->nullable()->index();

            // Cart totals (denormalized for performance)
            $table->integer('item_count')->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);

            // Applied coupon
            $table->string('coupon_code')->nullable();
            $table->decimal('coupon_discount', 12, 2)->default(0);

            // Selected shipping method
            $table->string('shipping_method')->nullable();
            $table->json('shipping_address')->nullable();

            // Cart status
            $table->enum('status', ['active', 'abandoned', 'converted'])->default('active');

            // Expiry tracking (for abandoned carts)
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Ensure only one active cart per user/session
            $table->unique(['user_id', 'status'], 'unique_user_active_cart')
                  ->where('status', 'active');
            $table->unique(['session_id', 'status'], 'unique_session_active_cart')
                  ->where('status', 'active');

            $table->index(['status', 'expires_at']);
            $table->index('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
