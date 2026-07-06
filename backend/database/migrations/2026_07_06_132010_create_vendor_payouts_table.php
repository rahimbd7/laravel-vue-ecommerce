<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('commission', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->decimal('platform_fee', 15, 2)->default(0);
            $table->enum('status', ['pending', 'processing', 'paid', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['vendor_id', 'status']);
            $table->index(['order_id', 'vendor_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_payouts');
    }
};