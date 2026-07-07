<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // ✅ NEW: Add store logo column
            $table->string('store_logo')->nullable()->after('description');
            // ✅ NEW: Add shipping settings JSON column
            $table->json('shipping_settings')->nullable()->after('commission_rate');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['store_logo', 'shipping_settings']);
        });
    }
};
