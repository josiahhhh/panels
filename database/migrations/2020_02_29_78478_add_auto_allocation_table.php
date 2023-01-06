<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAutoAllocationTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('auto_allocation_adder', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('egg_id');
            $table->text('allocations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('auto_allocation_adder');
    }
}
