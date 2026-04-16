<?php
// database/migrations/2026_04_11_000001_add_guest_token_to_carts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            // Add guest_token column for persistent guest carts
            $table->string('guest_token', 100)->nullable()->unique()->after('session_id');
            $table->index('guest_token');

            // Change session_id to be nullable since we'll use guest_token
            $table->string('session_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('guest_token');
            $table->string('session_id')->nullable(false)->change();
        });
    }
};
