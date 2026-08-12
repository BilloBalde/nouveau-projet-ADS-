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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('facture_id')->unsigned();
            $table->foreign('facture_id')->references('id')->on('factures')->onUpdate('cascade')->onDelete('restrict');
            $table->decimal("versement", 15,2);
            $table->decimal("total_paye", 15,2);
            $table->decimal("reste", 15,2);
            $table->enum('paid_by', ['cash', 'check', 'orange money'])->default('cash');
            $table->string('note');
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
        Schema::dropIfExists('payments');
    }
};
