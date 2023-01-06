<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddModImageColumn extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('mod_manager_mods', function (Blueprint $table) {
            $table->text('image')->nullable()->after('install_folder');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('mod_manager_mods', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
}
