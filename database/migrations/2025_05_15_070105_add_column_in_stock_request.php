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
        if (!Schema::hasColumn('stock_requests', 'total')) {
            Schema::table('stock_requests', function (Blueprint $table) {
                $table->decimal('total', 15, 2)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('stock_requests', 'total')) {
            Schema::table('stock_requests', function (Blueprint $table) {
                $table->dropColumn('total');
            });
        }
    }
};
