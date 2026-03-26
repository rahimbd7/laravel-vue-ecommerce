<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('image_url');
            $table->string('thumbnail_url')->nullable();
            $table->string('medium_url')->nullable();
            $table->string('large_url')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('alt_text')->nullable();
            $table->string('title')->nullable();
            $table->string('caption')->nullable();

            $table->integer('order')->default(0);
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('file_size')->nullable(); // in bytes

            $table->timestamps();

            // Indexes
            $table->index('product_id');
            $table->index('is_primary');
            $table->index(['product_id', 'is_primary']);
            $table->index(['product_id', 'order']);

            // Ensure only one primary image per product
            $table->unique(['product_id', 'is_primary'], 'unique_primary_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('product_images');
    }
};
