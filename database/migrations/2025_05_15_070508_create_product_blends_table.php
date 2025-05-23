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
// produk 
// produk detIL

        Schema::create('product_blends', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->nullable()->constrained();
            $table->foreignUuid('store_id')->constrained();
            $table->foreignUuid('warehouse_id')->constrained();
            $table->double('result_stock'); //nambah stock
            $table->foreignUuid('unit_id')->nullable()->constrained();
            $table->dateTime('date');
            $table->timestamps();
        });


        //product_stock => membuat / menambah stock baru
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_blends');
    }
};
