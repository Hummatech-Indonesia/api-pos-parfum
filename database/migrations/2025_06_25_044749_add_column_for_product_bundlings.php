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
        Schema::table('product_bundlings', function (Blueprint $table) {
            if (!Schema::hasColumn('product_bundlings', 'stock')) {
                $table->integer('stock')->nullable();
            }

            if (!Schema::hasColumn('product_bundlings', 'price')) {
                $table->double('price')->nullable();
            }

            if (!Schema::hasColumn('product_bundlings', 'price')) {
                $table->string('bundling_code')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_bundlings', function (Blueprint $table) {
            if (!Schema::hasColumn('product_bundlings', 'stock')) {
                $table->dropColumn('stock');
            }

            if (!Schema::hasColumn('product_bundlings', 'price')) {
                $table->dropColumn('price');
            }

            if (!Schema::hasColumn('product_bundlings', 'price')) {
                $table->dropColumn('bundling_code');
            }
        });
    }
};
