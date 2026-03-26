<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            // Existing unique(product_id, is_primary) allows only one row with is_primary=0 too,
            // which breaks multiple image inserts. Drop it and enforce primary logic in app layer.
            $table->dropUnique('unique_primary_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->unique(['product_id', 'is_primary'], 'unique_primary_image');
        });
    }
};
