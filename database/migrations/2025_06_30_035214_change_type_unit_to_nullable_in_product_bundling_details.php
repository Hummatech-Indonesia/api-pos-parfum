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
        Schema::table('product_bundling_details', function (Blueprint $table) {
            if (Schema::hasColumn('product_bundling_details', 'unit')) {
                $table->string('unit')->nullable()->change();
            }

            if (Schema::hasColumn('product_bundling_details', 'quantity')) {
                $table->double('quantity')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_bundling_details', function (Blueprint $table) {
            if (Schema::hasColumn('product_bundling_details', 'unit')) {
                $table->string('unit')->nullable(false)->change();
            }

            if (Schema::hasColumn('product_bundling_details', 'quantity')) {
                $table->double('quantity')->nullable(false)->change();
            }
        });
    }
};
