<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            // $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');

            $table->integer('rating'); // 1-5
            $table->string('title')->nullable();
            $table->text('comment');

            $table->json('pros')->nullable(); // Positive aspects
            $table->json('cons')->nullable(); // Negative aspects

            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);

            $table->integer('helpful_votes')->default(0);
            $table->integer('unhelpful_votes')->default(0);

            $table->json('images')->nullable(); // Review images

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('product_id');
            $table->index('user_id');
            $table->index('rating');
            $table->index(['product_id', 'is_approved']);
            $table->index(['product_id', 'rating', 'is_approved']);
            $table->index('created_at');

            // Ensure one review per user per product
            $table->unique(['product_id', 'user_id'], 'unique_user_product_review');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('product_reviews');
    }
};
