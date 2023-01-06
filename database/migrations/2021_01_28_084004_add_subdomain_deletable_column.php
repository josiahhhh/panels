<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubdomainDeletableColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subdomain_manager_subdomains', function (Blueprint $table) {
            $table->boolean('deletable')->default(true)->after('record_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subdomain_manager_subdomains', function (Blueprint $table) {
            $table->dropColumn('deletable');
        });
    }
}
