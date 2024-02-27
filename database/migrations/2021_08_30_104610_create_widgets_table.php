<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWidgetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('widgets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('resource')->nullable();
            $table->longText('resource_data')->nullable();
            $table->string('title')->default('Undefined Widget');
            $table->boolean('status')->default(0);
            $table->timestamps();
        });
        
        Schema::create('widget_translations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('widget_id')->index();
            $table->string('lang', 2)->default(config('app.locale'));
            $table->longText('data')->nullable();
            $table->timestamps();
            
            $table->foreign('widget_id')
                ->references('id')->on('widgets')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('widgets');
        Schema::dropIfExists('widget_translations');
    }
}
