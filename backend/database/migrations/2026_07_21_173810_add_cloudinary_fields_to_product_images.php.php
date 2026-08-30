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
        Schema::table('product_images', function (Blueprint $table) {
            $table->string('cloudinary_public_id')->nullable()->after('large_url');
            $table->string('cloudinary_asset_id')->nullable()->after('cloudinary_public_id');
            $table->string('cloudinary_version')->nullable()->after('cloudinary_asset_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn([
                'cloudinary_public_id',
                'cloudinary_asset_id',
                'cloudinary_version'
            ]);
        });
    }
};
