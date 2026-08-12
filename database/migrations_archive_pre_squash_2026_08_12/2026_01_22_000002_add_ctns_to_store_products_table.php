<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('store_products', function (Blueprint $table) {
            $table->integer('ctns')->default(0)->after('quantity');
        });
    }

    public function down()
    {
        Schema::table('store_products', function (Blueprint $table) {
            $table->dropColumn('ctns');
        });
    }
};
