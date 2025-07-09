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
        if(!Schema::hasTable('audit_details'))
        Schema::create('audit_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('audit_id')->constrained();
            $table->foreignUuid('product_detail_id')->constrained();
            $table->double('old_stock')->default(0);
            $table->double('audit_stock')->default(0);
            $table->foreignUuid('unit_id')->constrained();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_details');
    }
};
