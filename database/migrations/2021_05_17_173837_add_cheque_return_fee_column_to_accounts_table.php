<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChequeReturnFeeColumnToAccountsTable extends Migration
{
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('cheque_return_fee')->nullable()->after('note');
        });
    }

    public function down()
    {
        Schema::table('accounts', function (Blueprint $table) {
            //
        });
    }
}