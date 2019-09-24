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
        Schema::create('databases', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('server_id');
            $table->timestamps();
        });

        Schema::create('servers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('owner_id');
            $table->unsignedInteger('team_id')->nullable();
            $table->string('name');
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
