<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBackupRestoreStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('backup_restore_status', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('server_id')->unsigned();
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');

            // Specifies if the restore is for a file or database backup
            $table->string('type');

            // Optional: specified if the restore is for a database backup
            $table->integer('database_backup_id')->unsigned()->nullable();
            $table->foreign('database_backup_id')->references('id')->on('database_backups');

            // Optional: specified if the restore is for a file backup
            $table->bigInteger( 'backup_id')->unsigned()->nullable();
            $table->foreign('backup_id')->references('id')->on('backups');

            $table->boolean('is_successful')->nullable();
            $table->string('error')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('backup_restore_status');
    }
}
