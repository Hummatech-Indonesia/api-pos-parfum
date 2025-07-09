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
        if (Schema::hasColumn('products', 'product_varian_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('product_varian_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('products', 'product_varian_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('product_varian_id')->nullable(); // Tambahkan nullable jika perlu
            });
        }
    }
};
