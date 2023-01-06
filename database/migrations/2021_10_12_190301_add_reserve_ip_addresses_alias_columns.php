<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReserveIpAddressesAliasColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('reserve_ip_addresses', function (Blueprint $table) {
            $table->text('alias')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('reserve_ip_addresses', function (Blueprint $table) {
            $table->dropColumn('alias');
        });
    }
}
