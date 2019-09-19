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
        Schema::create('firewalls', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('network_id')->nullable();
            $table->timestamps();
        });

        Schema::create('networks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('server_id')->nullable();
            $table->timestamps();
        });

        Schema::create('servers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('owner_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('websites', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('server_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });
    }
}
