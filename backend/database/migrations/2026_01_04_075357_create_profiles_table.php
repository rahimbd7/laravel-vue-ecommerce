<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('user_uuid');
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->timestamps();
            $table->index('user_uuid');
            //foreign key constraint
            $table->foreign('user_uuid')->references('uuid')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('profiles');
    }
};
