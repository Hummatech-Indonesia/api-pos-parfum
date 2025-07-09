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
        if (!Schema::hasColumn('discount_vouchers', 'product_detail_id')) {
            Schema::table('discount_vouchers', function (Blueprint $table) {
                $table->foreignUuid('product_detail_id')->nullable()->constrained('product_details');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discount_vouchers', function (Blueprint $table) {
            //
        });
    }
};
