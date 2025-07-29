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
        if(!Schema::hasTable('pengeluaran')) {
            Schema::create('kategori_pengeluaran', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string("nama");
                $table->foreignUuid('outlet_id')
                    ->constrained('outlets')
                    ->onDelete("cascade");
                $table->foreignUuid('warehouse_id')
                    ->constrained('warehouses')
                    ->onDelete("cascade");
                $table->tinyInteger('is_delete')->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_pengeluaran');
    }
};
