<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddModCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('mod_manager_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::table('mod_manager_mods', function (Blueprint $table) {
            $table->text('categories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('mod_manager_categories');

        Schema::table('mod_manager_mods', function (Blueprint $table) {
            $table->dropColumn('categories');
        });
    }
}
