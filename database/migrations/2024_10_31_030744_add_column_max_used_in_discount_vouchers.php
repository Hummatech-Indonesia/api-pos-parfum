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
        if(!Schema::hasColumn('products','total')){
            Schema::table('products', function (Blueprint $table) {
                $table->double('total')->default(0);
            });
        }
        if(!Schema::hasColumn('discount_vouchers','max_used')){
            Schema::table('discount_vouchers', function (Blueprint $table) {
                $table->integer('max_used')->nullable();
            });
        }
        if(!Schema::hasColumn('discount_vouchers','type')){
            Schema::table('discount_vouchers', function (Blueprint $table) {
                $table->string('type')->nullable();
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
