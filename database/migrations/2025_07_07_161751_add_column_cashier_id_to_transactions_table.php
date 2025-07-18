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
        if (!Schema::hasColumn('transactions', 'cashier_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreignUuid('cashier_id')->nullable()->constrained('users');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'cashier_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('cashier_id');
            });
        }
    }
};
