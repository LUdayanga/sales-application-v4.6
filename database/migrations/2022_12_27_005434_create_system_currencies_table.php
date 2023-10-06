<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSystemCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_currencies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('currency_name')->nullable();
            $table->string('currency_symbol')->nullable();
            $table->decimal('currency_rate', 22, 2)->default(0);
            $table->integer('is_show_currency')->default(0);
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
        Schema::dropIfExists('system_currencies');
    }
}
