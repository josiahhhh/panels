<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNodesIPAddressColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('nodes', function (Blueprint $table) {
            $table->text('ip_address')->nullable(true)->after('fqdn');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('ip_address');
        });
    }
}
