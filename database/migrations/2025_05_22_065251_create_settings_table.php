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
        if(!Schema::hasTable('settings'))
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('descriptions');
            $table->string('code');
            $table->boolean('value_active')->nullable();
            $table->text('value_text')->nullable();
            $table->string('group');
            $table->foreignUuid('store_id')->constrained();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
