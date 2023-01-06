<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDatabaseHostsLocationIdColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('database_hosts', function (Blueprint $table) {
            $table->unsignedInteger('location_id')->nullable(true);
        });

        Schema::table('database_hosts', function (Blueprint $table) {
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('database_hosts', function (Blueprint $table) {
            $table->dropForeign('location_id');
        });

        Schema::table('database_hosts', function (Blueprint $table) {
            $table->dropColumn('location_id');
        });
    }
}
