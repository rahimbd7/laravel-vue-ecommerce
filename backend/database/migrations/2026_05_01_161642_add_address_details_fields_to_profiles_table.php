<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
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
                $table->string('country', 2)->nullable()->after('postal_code');
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
