<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->double('total_taken', 15,2)->default(0.0)->after('address');
            $table->double('total_repaid', 15,2)->default(0.0)->after('total_taken');
            $table->double('balance', 15,2)->default(0.0)->after('total_repaid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['total_taken', 'total_repaid', 'balance']);
        });
    }
};
