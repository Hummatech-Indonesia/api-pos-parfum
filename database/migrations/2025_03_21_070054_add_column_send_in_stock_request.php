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
        if(!Schema::hasColumn('stock_requests','send')){
            Schema::table('stock_requests', function (Blueprint $table) {
                $table->double('send')->nullable();
            });
        }
        
        if(Schema::hasColumn('stock_requests', 'product_detail_id')){
            Schema::table('stock_requests', function (Blueprint $table) {
                $table->foreignUuid('product_detail_id')->nullable()->change();
            });
        }

        if(Schema::hasColumn('stock_requests', 'requested_stock')){
            Schema::table('stock_requests', function (Blueprint $table) {
                $table->double('requested_stock')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_request', function (Blueprint $table) {
            //
        });
    }
};
