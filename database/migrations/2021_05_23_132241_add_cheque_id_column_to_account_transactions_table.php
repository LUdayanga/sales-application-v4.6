<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChequeIdColumnToAccountTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('cheque_id')->nullable()->after('transfer_transaction_id');
        });
    }

    public function down()
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            //
        });
    }
}