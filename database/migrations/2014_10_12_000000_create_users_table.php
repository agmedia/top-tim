<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
            $table->text('profile_photo_path')->nullable();
            $table->timestamps();
        });
        
        Schema::create('user_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('fname');
            $table->string('lname')->nullable();
            $table->string('address')->nullable();
            $table->string('zip')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('phone')->nullable();
            $table->string('avatar')->default('images/avatars/default_avatar.jpg');
            $table->longText('bio')->nullable();
            $table->string('social')->nullable();
            $table->string('default_lang', 2)->nullable();
            $table->string('role');
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
        
        Schema::create('user_emails', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('target');
            $table->string('key')->nullable();
            $table->longText('mail_data')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->tinyInteger('clicked')->unsigned()->default(0);
            $table->text('comment')->nullable();
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
        Schema::dropIfExists('users');
        Schema::dropIfExists('user_details');
        Schema::dropIfExists('user_emails');
    }
}
