<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('brand_id')->unsigned()->default(0);
            $table->bigInteger('action_id')->unsigned()->default(0);
            $table->string('sku', 14)->default(0)->index();
            $table->string('ean', 14)->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 15, 4)->default(0);
            $table->integer('quantity')->unsigned()->default(0);
            $table->tinyInteger('decrease')->unsigned()->default(0);
            $table->integer('tax_id')->unsigned()->default(0);
            $table->decimal('special', 15, 4)->nullable();
            $table->timestamp('special_from')->nullable();
            $table->timestamp('special_to')->nullable();
            $table->string('related_products')->nullable();
            $table->boolean('vegan')->default(false);
            $table->boolean('vegetarian')->default(false);
            $table->boolean('glutenfree')->default(false);
            $table->tinyInteger('viewed')->unsigned()->default(0);
            $table->integer('sort_order')->unsigned()->default(0);
            $table->boolean('push')->default(false);
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
        
        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('lang', 2)->default(config('app.locale'));
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->text('podaci')->nullable();
            $table->text('sastojci')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('slug');
            $table->string('url', 255);
            $table->string('tags')->nullable();
            $table->timestamps();
            
            $table->foreign('product_id')
                ->references('id')->on('products')
                ->onDelete('cascade');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('image');
            $table->boolean('default')->default(false);
            $table->boolean('published')->default(false);
            $table->integer('sort_order')->unsigned();
            $table->timestamps();

            $table->foreign('product_id')
                  ->references('id')->on('products');
        });

        Schema::create('product_images_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_image_id')->index();
            $table->string('lang', 2)->default(config('app.locale'));
            $table->string('title')->nullable();
            $table->string('alt')->nullable();
            $table->timestamps();

            $table->foreign('product_image_id')
                  ->references('id')->on('product_images')
                  ->onDelete('cascade');
        });

        Schema::create('product_actions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type');
            $table->decimal('discount', 15, 4);
            $table->string('group');
            $table->text('links')->nullable();
            $table->timestamp('date_start')->nullable();
            $table->timestamp('date_end')->nullable();
            $table->string('badge')->nullable();
            $table->decimal('min_cart', 15, 4)->nullable();
            $table->boolean('logged')->default(0);
            $table->integer('quantity')->unsigned()->default(0);
            $table->integer('viewed')->unsigned()->default(0);
            $table->integer('clicked')->unsigned()->default(0);
            $table->boolean('status')->default(0);
            $table->timestamps();
        });
        
        Schema::create('product_actions_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_action_id')->index();
            $table->string('lang', 2)->default(config('app.locale'));
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('product_action_id')
                ->references('id')->on('product_actions')
                ->onDelete('cascade');
        });

        Schema::create('product_category', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('category_id')->index();

            $table->foreign('product_id')
                  ->references('id')->on('products');

            $table->foreign('category_id')
                  ->references('id')->on('categories');
        });

        Schema::create('product_attribute', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('attribute_id')->index();

            $table->foreign('product_id')
                  ->references('id')->on('products');

            $table->foreign('attribute_id')
                  ->references('id')->on('attributes');
        });

        Schema::create('product_option', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('option_id')->index();
            $table->string('sku', 14)->default(0)->index();
            $table->integer('quantity')->unsigned()->default(0);
            $table->decimal('price', 15, 4)->default(0);
            $table->text('data')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreign('product_id')
                  ->references('id')->on('products');

            $table->foreign('option_id')
                  ->references('id')->on('options');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_translations');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_actions');
        Schema::dropIfExists('product_actions_translations');
        Schema::dropIfExists('product_category');
        Schema::dropIfExists('product_attribute');
    }
}



