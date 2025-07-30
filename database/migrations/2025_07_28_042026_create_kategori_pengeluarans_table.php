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
                    ->nullable()
                    ->references('id')
                    ->on('outlets');
                $table->foreignUuid('warehouse_id')
                    ->nullable()
                    ->references('id')
                    ->on('warehouses');
                $table->softDeletes();
                $table->timestamps();
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
