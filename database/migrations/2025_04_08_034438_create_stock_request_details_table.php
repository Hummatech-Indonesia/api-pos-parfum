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
        Schema::create('stock_request_details', function (Blueprint $table) {
            // UUID primary key
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_request_id')->nullable()->constrained('stock_requests');
            $table->foreignUuid('product_detail_id')->nullable()->constrained('product_details');
            $table->integer('requested_stock')->default(0); // requested_stock as int(11)
            $table->integer('sended_stock')->default(0); // sended_stock as int(11)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_request_details');
    }
};
