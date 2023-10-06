<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQualityControllProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quality_controll_products', function (Blueprint $table) {
            $table->bigIncrements('qc_product_table_id');
            $table->integer('qc_id');
            $table->integer('product_id');
            $table->integer('variation_id');
            $table->integer('transaction_id')->nullable();
            $table->double('recieved_qty');
            $table->double('qc_checked_qty');
            $table->double('qc_pass_qty');
            $table->double('qc_fail_qty');
            $table->string('product_lot_no');
            $table->string('product_qc_step');
            $table->string('qc_fail_description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quality_controll_products');
    }
}
