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
        Schema::table('stock_request_details', function (Blueprint $table) {
            $table->foreignUuid('unit_id')->constrained('units')->nullable()->after('product_detail_id');
            $table->string('unit')->nullable()->after('unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_request_details', function (Blueprint $table) {
            $table->dropColumn(['unit_id', 'unit']);
        });
    }
};
