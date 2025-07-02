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
        if (!Schema::hasColumn('warehouses', 'person_responsible')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->string('person_responsible')->nullable()->after('telp');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('warehouses', 'person_responsible')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->dropColumn('person_responsible');
            });
        }
    }
};
