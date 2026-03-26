<?php
// database/migrations/2026_03_26_094500_add_missing_fields_to_profiles_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // Add missing address fields if not exists
            if (!Schema::hasColumn('profiles', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            if (!Schema::hasColumn('profiles', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (!Schema::hasColumn('profiles', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('state');
            }
            if (!Schema::hasColumn('profiles', 'country')) {
                $table->string('country')->nullable()->after('postal_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['city', 'state', 'postal_code', 'country']);
        });
    }
};
