<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLandmarkColumnToContactsTable extends Migration
{
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            // $table->string('landmark')->nullable()->after('country');
        });
    }

    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            //
        });
    }
}