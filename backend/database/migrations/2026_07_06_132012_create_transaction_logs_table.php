<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_id')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('user_role'); // customer, vendor, admin
            $table->string('action'); // payment, payout, refund, commission, adjustment
            $table->string('reference_type'); // order, payment, payout, refund
            $table->bigInteger('reference_id');
            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('USD');
            $table->string('status');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'reference_type', 'reference_id']);
            $table->index(['action', 'status']);
            $table->index('log_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_logs');
    }
};