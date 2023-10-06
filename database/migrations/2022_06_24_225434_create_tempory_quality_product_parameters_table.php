<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTemporyQualityProductParametersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tempory_quality_product_parameters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('parent_parameter_id');
            $table->integer('parent_product_id');
            $table->string('qc_param_status');
            $table->string('qc_param_description');
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
        Schema::dropIfExists('tempory_quality_product_parameters');
    }
}
