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
        if (!Schema::hasColumn('product_details', 'unit_id')) {
            Schema::table('product_details', function (Blueprint $table) {
                $table->foreignUuid('unit_id')->nullable()->constrained();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('product_details', 'unit_id')) {
            Schema::table('product_details', function (Blueprint $table) {
                $table->dropColumn('unit_id');
            });
        }
    }
};
