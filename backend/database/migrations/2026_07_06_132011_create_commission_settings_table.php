<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('commission_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->onDelete('cascade');
            $table->decimal('rate', 5, 2)->default(10.00);
            $table->string('type')->default('percentage'); // percentage, fixed
            $table->boolean('is_default')->default(false);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->timestamps();
            
            $table->index(['vendor_id', 'is_default']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('commission_settings');
    }
};