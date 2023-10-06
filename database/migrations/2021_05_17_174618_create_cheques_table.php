<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChequesTable extends Migration
{
    public function up()
    {
        Schema::create('cheques', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('cheque_number');
            $table->timestamp('cheque_date')->nullable();
            $table->timestamp('cheque_issued_date')->nullable();
            $table->decimal('cheque_amount')->nullable();
            $table->enum('cheque_status', ['due', 'partial', 'paid', 'return'])->default('due');
            $table->enum('cheque_type', ['giving', 'receiving']);
            $table->unsignedInteger('account_id');
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts');
            $table->index(['id', 'account_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cheques');
    }
}