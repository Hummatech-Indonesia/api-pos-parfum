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
        if (!Schema::hasColumn('discount_vouchers', 'warehouse_id')) {
            Schema::table('discount_vouchers', function (Blueprint $table) {
                $table->foreignUuid('warehouse_id')->nullable()->constrained();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('discount_vouchers', 'warehouse_id')) {
            Schema::table('discount_vouchers', function (Blueprint $table) {
                $table->dropColumn('warehouse_id');
            });
        }
    }
};
