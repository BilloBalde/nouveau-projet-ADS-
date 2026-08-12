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
        Schema::table('transaction_factures', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->unique()->after('customer_id');
            $table->string('paid_by')->nullable()->after('receipt_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_factures', function (Blueprint $table) {
            $table->dropUnique(['receipt_number']);
            $table->dropColumn(['receipt_number', 'paid_by']);
        });
    }
};
