<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChequeIdColumnToTransactionPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('cheque_id')->nullable()->after('account_id');

            $table->foreign('cheque_id')->references('id')->on('cheques');
            $table->index('cheque_id');
        });
    }

    public function down()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            //
        });
    }
}