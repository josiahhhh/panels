<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDatabaseBackups extends Migration {

    /**
     * Run the migrations.
     */
    public function up() {
        Schema::create('database_backups', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uuid', 36)->unique();
            $table->integer('server_id')->unsigned();
            $table->integer('database_id')->unsigned();
            $table->string('name');
            $table->boolean('is_successful')->nullable();
            $table->string('error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('bytes')->default(0);
            $table->timestamps();

            // TODO: cascade trigger backup deletion?
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            $table->foreign('database_id')->references('id')->on('databases')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down() {
        Schema::dropIfExists('database_backups');
    }
}
