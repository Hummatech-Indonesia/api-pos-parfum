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
        if(!Schema::hasColumn('outlets','image')){
            Schema::table('outlets', function (Blueprint $table) {
                $table->text('image')->nullable();
            });
        }

        if(!Schema::hasColumn('warehouses','image')){
            Schema::table('warehouses', function (Blueprint $table) {
                $table->text('image')->nullable();
            });
        }

        if(Schema::hasColumn('product_details','category_id')){
            Schema::table('product_details', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outlet_warehouse', function (Blueprint $table) {
            //
        });
    }
};
