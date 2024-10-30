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
        if(!Schema::hasColumn('stores','tax')){
            Schema::table('stores', function (Blueprint $table) {
                $table->integer('tax')->default(0);
            });
        }
        if(!Schema::hasColumn('categories','is_delete')){
            Schema::table('categories', function (Blueprint $table) {
                $table->tinyInteger('is_delete')->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
