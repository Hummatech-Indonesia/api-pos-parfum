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
        if(!Schema::hasTable('product_blend_details'))
        Schema::create('product_blend_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_blend_id')->nullable()->constrained();
            $table->foreignUuid('product_detail_id')->nullable()->constrained();
            $table->double('used_stock'); //mengurangi stock
            $table->foreignUuid('unit_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_blend_details');
    }
};
