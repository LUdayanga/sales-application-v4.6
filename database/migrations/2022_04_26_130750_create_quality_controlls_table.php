<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQualityControllsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quality_controlls', function (Blueprint $table) {
            $table->bigIncrements('qc_id');
            $table->date('qc_date')->nullable();
            $table->string('qc_ref_no')->nullable();
            $table->string('qc_type')->nullable();
            $table->string('qc_step')->nullable();
            $table->string('qc_status')->nullable();
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
        Schema::dropIfExists('quality_controlls');
    }
}
