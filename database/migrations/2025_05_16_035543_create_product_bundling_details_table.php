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
        if(!Schema::hasTable('product_bundling_details'))
        Schema::create('product_bundling_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_bundling_id')->constrained('product_bundlings');
            $table->foreignUuid('product_detail_id')->constrained('product_details');
            $table->string('unit');
            $table->foreignUuid('unit_id')->constrained('units');
            $table->double('quantity');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_bundling_details');
    }
};
