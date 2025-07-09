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
        if(!Schema::hasTable('audits'))
        Schema::create('audits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained();
            $table->string('name');
            $table->text('description');
            $table->enum('status',['pending','approved','rejected'])->default('pending');
            $table->string('reason')->nullable();
            $table->foreignUuid('store_id')->constrained();
            $table->date('date');
            $table->foreignUuid('outlet_id')->constrained();
            $table->softDeletes();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
