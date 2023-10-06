<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsOnQualityControllsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quality_controlls', function (Blueprint $table) {
            $table->string('ref_doc_no')->after('special_note')->nullable();
            $table->string('ref_type')->after('ref_doc_no')->nullable();
            $table->string('transaction_id')->after('ref_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
