<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDisableModCacheColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('mod_manager_mods', function (Blueprint $table) {
            $table->boolean('disable_cache')->default(false)->after('restart_on_install');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('mod_manager_mods', function (Blueprint $table) {
            $table->dropColumn('disable_cache');
        });
    }
}
