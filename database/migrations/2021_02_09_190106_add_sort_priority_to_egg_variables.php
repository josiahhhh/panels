<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortPriorityToEggVariables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('egg_variables', function (Blueprint $table) {
            $table->integer('sort_priority')->default(0)->after('rules');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('egg_variables', function (Blueprint $table) {
            $table->dropColumn('sort_priority');
        });
    }
}
