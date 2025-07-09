<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_bundling_details', function (Blueprint $table) {
            // Drop foreign key constraint terlebih dahulu
            $table->dropForeign(['unit_id']);
        });

        Schema::table('product_bundling_details', function (Blueprint $table) {
            $table->dropColumn('unit_id');
        });

        Schema::table('product_bundling_details', function (Blueprint $table) {
            $table->foreignUuid('unit_id')->nullable()->constrained('units');
        });
    }

    public function down(): void
    {
        Schema::table('product_bundling_details', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
        });

        Schema::table('product_bundling_details', function (Blueprint $table) {
            $table->dropColumn('unit_id');
        });

        Schema::table('product_bundling_details', function (Blueprint $table) {
            $table->foreignUuid('unit_id')->constrained('units');
        });
    }
};
