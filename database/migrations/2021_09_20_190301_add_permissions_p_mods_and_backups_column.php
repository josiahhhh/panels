<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPermissionsPModsAndBackupsColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('permissions', function (Blueprint $table) {
            $table->unsignedTinyInteger('p_mods')->default(0);
            $table->unsignedTinyInteger('p_backups')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('p_mods');
            $table->dropColumn('p_backups');
        });
    }
}
