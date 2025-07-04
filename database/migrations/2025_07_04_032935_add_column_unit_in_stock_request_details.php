<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('stock_request_details')) return;

        Schema::table('stock_request_details', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_request_details', 'unit_id')) {
                if (Schema::hasTable('units')) {
                    $table->foreignUuid('unit_id')->nullable()->constrained('units')->after('product_detail_id');
                } else {
                    $table->uuid('unit_id')->nullable()->after('product_detail_id');
                }
            }

            if (!Schema::hasColumn('stock_request_details', 'unit')) {
                $table->string('unit')->nullable()->after('unit_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('stock_request_details')) return;

        Schema::table('stock_request_details', function (Blueprint $table) {
            if (Schema::hasColumn('stock_request_details', 'unit_id')) {
                $table->dropForeign(['unit_id']);
                $table->dropColumn('unit_id');
            }

            if (Schema::hasColumn('stock_request_details', 'unit')) {
                $table->dropColumn('unit');
            }
        });
    }
};

