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
        Schema::create('discount_vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores');
            $table->foreignUuid('product_id')->nullable()->constrained('products');
            $table->foreignUuid('outlet_id')->nullable()->constrained('outlets');
            $table->string('name');
            $table->text('desc')->nullable();
            $table->integer('min')->default(0);
            $table->integer('discount')->default(1);
            $table->date('expired')->nullable();
            $table->tinyInteger('is_delete')->default(0);
            $table->tinyInteger('active')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_vouchers');
    }
};
