<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->softDeletes(); // ini akan menambahkan kolom deleted_at nullable
        });
    }

    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }

};
