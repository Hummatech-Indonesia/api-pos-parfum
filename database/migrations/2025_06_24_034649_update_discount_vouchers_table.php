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
        Schema::table('discount_vouchers', function (Blueprint $table) {
            
            if (Schema::hasColumn('discount_vouchers', 'max_used')) {
                Schema::table('discount_vouchers', function (Blueprint $table) {
                    $table->dropColumn('max_used');
                });
            }

            if (!Schema::hasColumn('discount_vouchers', 'nominal')) {
                Schema::table('discount_vouchers', function (Blueprint $table) {
                    $table->integer('nominal')->nullable()->after('discount');
                });
            }

            if (!Schema::hasColumn('discount_vouchers', 'percentage')) {
                Schema::table('discount_vouchers', function (Blueprint $table) {
                    $table->integer('percentage')->nullable()->after('nominal');
                });
            }
        });
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
