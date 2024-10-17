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
        if(!Schema::hasColumn('users','outlet_id')){
            Schema::table('users', function (Blueprint $table) {
                $table->foreignUuid('outlet_id')->nullable()->constrained();
            });
        }
        if(!Schema::hasColumn('users','warehouse_id')){
            Schema::table('users', function (Blueprint $table) {
                $table->foreignUuid('warehouse_id')->nullable()->constrained();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
