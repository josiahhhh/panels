<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBackupSettingsBackupRetentionColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('backup_settings', function (Blueprint $table) {
            $table->integer('backup_retention')->default(0)->after('server_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('backup_settings', function (Blueprint $table) {
            $table->dropColumn('backup_retention');
        });
    }
}
