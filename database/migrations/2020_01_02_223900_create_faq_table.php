<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFaqTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('faq', function (Blueprint $table) {
            $table->id();
            $table->string('group')->nullable();
            $table->string('sort_order')->nullable();
            $table->boolean('status')->nullable();
            $table->timestamps();
        });
        
        
        Schema::create('faq_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faq_id')->index();
            $table->string('lang', 2)->default(config('app.locale'));
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('faq_id')
                ->references('id')->on('faq')
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
        Schema::dropIfExists('faq');
        Schema::dropIfExists('faq_translations');
    }
}
