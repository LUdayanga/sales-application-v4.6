<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChequeTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('cheque_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('cheque_amount')->nullable();
            $table->unsignedBigInteger('cheque_id');
            $table->unsignedInteger('transaction_id');
            $table->unsignedInteger('contact_id');
            $table->timestamps();

            $table->foreign('cheque_id')->references('id')->on('cheques');
            $table->foreign('transaction_id')->references('id')->on('transactions');
            $table->foreign('contact_id')->references('id')->on('contacts');
            $table->index(['cheque_id', 'transaction_id', 'contact_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cheque_transactions');
    }
}