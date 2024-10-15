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
        if(!Schema::hasColumn('users','store_id')){
            Schema::table('users', function (Blueprint $table) {
                $table->foreignUuid('store_id')->nullable()->constrained();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(Schema::hasColumn('users','store_id')){
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('store_id');
            });
        }
    }
};
