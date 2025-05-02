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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('transaction_code')->nullable();
            $table->foreignUuid('store_id')->constrained();
            $table->foreignUuid('user_id')->nullable()->constrained();
            $table->string('user_name')->nullable();
            $table->double('amount_price')->default(0)->nullable();
            $table->double('amount_tax')->default(0)->nullable();
            $table->integer('tax')->default(0)->nullable();
            $table->double('total_price')->default(0)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('transaction_status');
            $table->datetime('payment_time')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
