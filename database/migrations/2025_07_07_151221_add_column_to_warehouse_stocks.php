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
        if (!Schema::hasColumn('warehouse_stocks', 'store_name')) {
            Schema::table('warehouse_stocks', function (Blueprint $table) {
                $table->string('store_name')->nullable();
            });
        }
        if (!Schema::hasColumn('warehouse_stocks', 'total_price')) {
            Schema::table('warehouse_stocks', function (Blueprint $table) {
                $table->double('total_price')->nullable()->default(0);
            });
        }
        if (!Schema::hasColumn('warehouse_stocks', 'store_location')) {
            Schema::table('warehouse_stocks', function (Blueprint $table) {
                $table->string('store_location')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            //
        });
    }
};
