<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServerBackupLimitBySizeColumn extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('servers', function (Blueprint $table) {
            $table->boolean('backup_limit_by_size')->after('backup_limit')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('backup_limit_by_size');
        });
    }
}
