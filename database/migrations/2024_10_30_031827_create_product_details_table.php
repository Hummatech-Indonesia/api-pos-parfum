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
        Schema::create('product_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignUuid('product_varian_id')->constrained('product_varians')->onDelete('cascade');
            $table->string('material')->nullable();
            $table->string('unit')->nullable();
            $table->integer('stock')->default(0);
            $table->integer('capacity')->default(0);
            $table->double('weight')->default(0);
            $table->double('density')->default(0); 
            $table->double('price')->default(0);
            $table->double('price_discount')->default(0);
            $table->tinyInteger('is_delete')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_details');
    }
};
