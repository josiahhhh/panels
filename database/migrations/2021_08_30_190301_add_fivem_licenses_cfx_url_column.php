<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFivemLicensesCfxUrlColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('fivem_licenses', function (Blueprint $table) {
            $table->text('cfx_url')->nullable(true)->after('key');
            $table->integer('server_id')->unsigned()->unique()->nullable()->after('id');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            $table->unique(['key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('fivem_licenses', function (Blueprint $table) {
            $table->dropForeign('fivem_licenses_server_id_foreign');
            $table->dropColumn('server_id');
            $table->dropColumn('cfx_url');
        });
    }
}
