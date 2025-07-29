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
            Schema::create('pengeluaran', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string("nama_pengeluaran");
                $table->foreignUuid("kategori_pengeluaran_id")
                    ->constrained("kategori_pengeluaran")
                    ->onDelete("cascade");
                $table->bigInteger('nominal_pengeluaran');
                $table->text('deskripsi');
                $table->string('image')->nullable();
                $table->date('tanggal_pengeluaran');
                $table->foreignUuid('outlet_id')
                    ->constrained('outlets')
                    ->onDelete("cascade");
                $table->foreignUuid('warehouse_id')
                    ->constrained('warehouses')
                    ->onDelete("cascade");
                $table->foreignId('category_id');
                $table->foreign("category_id")
                    ->references('id')
                    ->on('categories')
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
        Schema::dropIfExists('pengeluarans');
    }
};
