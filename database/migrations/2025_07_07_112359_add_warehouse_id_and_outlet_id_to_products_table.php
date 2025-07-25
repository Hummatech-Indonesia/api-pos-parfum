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
        if (!Schema::hasColumn('products', 'warehouse_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignUuid('warehouse_id')->nullable()->constrained();
            });
        }

        if (!Schema::hasColumn('products', 'outlet_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignUuid('outlet_id')->nullable()->constrained();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
