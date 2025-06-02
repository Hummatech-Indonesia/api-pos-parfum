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
        if (!Schema::hasColumn('stock_request_details', 'price')) {
            Schema::table('stock_request_details', function (Blueprint $table) {
                $table->decimal('price')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('stock_request_details', 'price')) {
            Schema::table('stock_request_details', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }
};
