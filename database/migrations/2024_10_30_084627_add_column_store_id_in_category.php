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
        if(!Schema::hasColumn('categories','store_id')){
            Schema::table('categories', function (Blueprint $table) {
                $table->foreignUuid('store_id')->nullable()->constrained('stores');
            });
        }
        if(!Schema::hasColumn('product_varians','store_id')){
            Schema::table('product_varians', function (Blueprint $table) {
                $table->foreignUuid('store_id')->nullable()->constrained('stores');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category', function (Blueprint $table) {
            //
        });
    }
};
