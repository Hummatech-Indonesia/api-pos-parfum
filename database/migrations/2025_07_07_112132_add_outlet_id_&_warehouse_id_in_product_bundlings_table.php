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
        if (!Schema::hasColumn('product_bundlings', 'outlet_id')) {
            Schema::table('product_bundlings', function (Blueprint $table) {
                $table->foreignUuid('outlet_id')->nullable()->constrained();
            });
        }

        if (!Schema::hasColumn('product_bundlings', 'warehouse_id')) {
            Schema::table('product_bundlings', function (Blueprint $table) {
                $table->foreignUuid('warehouse_id')->nullable()->constrained();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_bundlings', function (Blueprint $table) {
            //
        });
    }
};
