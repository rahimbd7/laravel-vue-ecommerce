<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Payment methods the admin allowed for this specific vendor.
            // Stored as JSON so a vendor can have any subset of the platform
            // gateways without further schema changes.
            $table->json('payment_methods')->nullable()->after('shipping_settings');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('payment_methods');
        });
    }
};