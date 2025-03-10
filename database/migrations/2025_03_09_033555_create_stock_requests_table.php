<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('stock_requests', function (Blueprint $table) {
            // UUID primary key
            $table->uuid('id')->primary();

            // UUID foreign keys
            $table->uuid('product_detail_id');
            $table->uuid('user_id');
            $table->uuid('outlet_id');
            $table->uuid('warehouse_id');

            // Additional columns
            $table->integer('requested_stock')->default(0); // requested_stock as int(11)
            $table->enum('status', ['pending', 'approved', 'rejected']); // status as ENUM
            $table->integer('is_delete')->default(0); 
            $table->text('note')->nullable(); // note as text, nullable if needed

            // Timestamps
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_requests');
    }
};
