<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('group')->default('default');
            $table->string('image')->nullable();
            $table->timestamp('publish_date')->nullable();
            $table->integer('viewed')->unsigned()->default(0);
            $table->boolean('featured')->default(false);
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
        
        
        Schema::create('page_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id')->index();
            $table->string('lang', 2)->default(config('app.locale'));
            $table->string('title');
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->longText('meta_description')->nullable();
            $table->string('slug')->index();
            $table->string('keywords')->nullable();
            $table->timestamps();
            
            $table->foreign('page_id')
                ->references('id')->on('pages')
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
        Schema::dropIfExists('pages');
        Schema::dropIfExists('page_translations');
    }
}



