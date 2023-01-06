<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddModRestartServerColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('mod_manager_mods', function (Blueprint $table) {
            $table->boolean('restart_on_install')->nullable()->after('install_folder');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('mod_manager_mods', function (Blueprint $table) {
            $table->dropColumn('restart_on_install');
        });
    }
}
