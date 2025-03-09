<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            // Change the 'id' column to UUID
            $table->dropPrimary('id'); // Drop existing primary key
            $table->uuid('id')->primary()->change(); // Change id to UUID

            // Change 'product_id' to UUID
            $table->uuid('product_id')->change();
            $table->uuid('user_id')->change();
            $table->uuid('outlet_id')->change();
            $table->uuid('warehouse_id')->change();

            // Remove 'tax' field
            $table->dropColumn('tax');
        });
    }

    public function down()
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            // Rollback: Change 'id' back to BIGINT
            $table->dropPrimary('id'); // Drop primary key
            $table->bigIncrements('id')->change(); // Revert to BIGINT primary key

            // Change 'product_id' back to BIGINT
            $table->bigInteger('product_id')->unsigned()->change();
            $table->bigInteger('user_id')->unsigned()->change();
            $table->bigInteger('outlet_id')->unsigned()->change();
            $table->bigInteger('warehouse_id')->unsigned()->change();

            // Restore 'tax' column (assuming it was a decimal)
            $table->decimal('tax', 10, 2)->nullable();
        });
    }
};