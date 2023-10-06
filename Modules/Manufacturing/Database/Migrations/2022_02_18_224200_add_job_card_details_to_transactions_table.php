<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddJobCardDetailsToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->text('doc_index')->nullable()->after('selling_price_group_id');
            $table->text('issue_no')->nullable()->after('doc_index');
            $table->text('section_id')->nullable()->after('issue_no');
            $table->text('shipment_no')->nullable()->after('section_id');
            $table->text('target_qty')->nullable()->after('shipment_no');
            $table->text('day_production_qty')->nullable()->after('target_qty');
            $table->text('no_workers')->nullable()->after('day_production_qty');
            $table->text('production_duration')->nullable()->after('no_workers');
            $table->text('expire_date')->nullable()->after('production_duration');
            $table->text('recovery')->nullable()->after('expire_date');
            $table->text('packing_instructions')->nullable()->after('recovery');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            //
        });
    }
}
