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
        if (Schema::hasColumn('product_bundlings', 'description')) {

            Schema::table('product_bundlings', function (Blueprint $table) {
                $table->dropColumn('description');
            });

            Schema::table('product_bundlings', function (Blueprint $table) {
                $table->text('description')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_bundlings', function (Blueprint $table) {
            //
        });
    }
};
