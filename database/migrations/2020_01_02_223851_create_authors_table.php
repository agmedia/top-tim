<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuthorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('letter', 2);
            $table->string('image')->default('media/avatars/avatar0.jpg');
            $table->integer('viewed')->unsigned()->default(0);
            $table->integer('featured')->unsigned()->default(0);
            $table->integer('sort_order')->unsigned()->default(0);
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
        
        Schema::create('brand_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brand_id')->index();
            $table->string('lang', 2)->default(config('app.locale'));
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->longText('meta_description')->nullable();
            $table->string('slug')->index();
            $table->string('url', 255);
            $table->string('keywords')->nullable();
            $table->timestamps();
            
            $table->foreign('brand_id')
                ->references('id')->on('brands')
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
        Schema::dropIfExists('brands');
        Schema::dropIfExists('brand_translations');
    }

}
