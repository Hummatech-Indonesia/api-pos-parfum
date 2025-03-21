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
        if(Schema::hasColumn('product_details','product_varian_id')){
            Schema::table('product_details', function (Blueprint $table) {
                $table->foreignUuid('product_varian_id')->nullable()->change();
            });
        }
        if(Schema::hasColumn('product_details','category_id')){
            Schema::table('product_details', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->change();
            });
        }
        if(!Schema::hasColumn('product_details','variant_name')){
            Schema::table('product_details', function (Blueprint $table) {
                $table->string('variant_name')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_details', function (Blueprint $table) {
            //
        });
    }
};
