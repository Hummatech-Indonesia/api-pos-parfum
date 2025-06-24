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
        if (Schema::hasColumn('product_details', 'product_varian_id')) {
            Schema::table('product_details', function (Blueprint $table) {
                $table->dropForeign(['product_varian_id']);
            });
        }

        if (Schema::hasTable('product_varians')) {
            Schema::dropIfExists('product_varians');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
