<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTempTableTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temp_table', function (Blueprint $table) {
            $table->bigInteger('product_id');
            $table->string('sku', 14)->nullable();
            $table->integer('quantity')->unsigned()->default(0);
            $table->decimal('price', 15, 4)->nullable();
            $table->decimal('special', 15, 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('temp_table');
    }
}
