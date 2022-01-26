<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('databases', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('server_id');
            $table->integer('administrator_id')->nullable();
            $table->timestamps();
        });

        Schema::create('servers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('team_id')->nullable();
            $table->string('name');
            $table->boolean('is_favorite')->nullable();
            $table->timestamps();
        });

        Schema::create('server_user', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('server_id');
            $table->integer('user_id');
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('server_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('team_user', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id');
            $table->integer('user_id');
            $table->timestamps();
        });
    }
}
