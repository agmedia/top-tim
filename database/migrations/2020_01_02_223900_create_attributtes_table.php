<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('group')->nullable();
            $table->string('type')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->nullable();
            $table->timestamps();
        });


        Schema::create('attributes_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attribute_id')->index();
            $table->string('lang', 2)->default(config('app.locale'));
            $table->string('group_title');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('attribute_id')
                ->references('id')->on('attributes')
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
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('attributes_translations');
    }
}
