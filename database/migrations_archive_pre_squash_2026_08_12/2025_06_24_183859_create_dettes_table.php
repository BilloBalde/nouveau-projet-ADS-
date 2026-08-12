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
        Schema::create('dettes', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->bigInteger('customer_id')->unsigned();
            $table->foreign('customer_id')->references('id')->on('customers')->onUpdate('cascade')->onDelete('restrict');
            $table->bigInteger('store_id')->unsigned();
            $table->foreign('store_id')->references('id')->on('stores')->onUpdate('cascade')->onDelete('restrict');
            $table->date('endettement_date');
            $table->double('montant_total', 10, 2);
            $table->double('montant_paid', 10, 2);
            $table->double('reste', 10, 2);
            $table->string('preuve')->default('default.png');
            $table->enum('status', ['pending', 'paid', 'cancel'])->default('pending');
            $table->text('notes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dettes');
    }
};
