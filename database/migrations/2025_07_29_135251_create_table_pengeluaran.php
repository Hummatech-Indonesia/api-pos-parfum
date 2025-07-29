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
                $table->bigInteger('nominal_pengeluaran');
                $table->text('deskripsi');
                $table->string('image')->nullable();
                $table->date('tanggal_pengeluaran');
                $table->foreignUuid("kategori_pengeluaran_id")
                    ->references('id')
                    ->on("kategori_pengeluaran")
                    ->onDelete("cascade");
                $table->foreignUuid('outlet_id')
                    ->nullable()
                    ->references('id')
                    ->on('outlets')
                    ->onDelete("cascade");
                $table->foreignUuid('warehouse_id')
                    ->nullable()
                    ->references('id')
                    ->on('warehouses')
                    ->onDelete("cascade");
                $table->foreignId('category_id');
                $table->foreign("category_id")
                    ->references('id')
                    ->on('categories')
                    ->onDelete("cascade");
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengeluaran');
    }
};
