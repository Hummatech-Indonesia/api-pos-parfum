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
        if (Schema::hasColumn('discount_vouchers', 'type')) {

            Schema::table('discount_vouchers', function (Blueprint $table) {
                $table->dropColumn('type');
            });

            Schema::table('discount_vouchers', function (Blueprint $table) {
                $table->enum('type', ['percentage', 'nominal'])->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('discount_vouchers', 'type')) {

            Schema::table('discount_vouchers', function (Blueprint $table) {
                $table->dropColumn('type');
            });

            Schema::table('discount_vouchers', function (Blueprint $table) {
                $table->string('type')->nullable();
            });
        }
    }
};
