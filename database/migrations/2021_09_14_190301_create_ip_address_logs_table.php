<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIPAddressLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ip_address_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('ip_address')->nullable(false);
            $table->text('to')->nullable();
            $table->unsignedInteger('node_id');
            $table->foreign('node_id')->references('id')->on('nodes');
            $table->text('reason');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ip_address_logs');
    }
}
