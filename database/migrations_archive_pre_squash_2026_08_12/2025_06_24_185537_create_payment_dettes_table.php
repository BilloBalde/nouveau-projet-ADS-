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
        Schema::create('payment_dettes', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->bigInteger('dette_id')->unsigned();
            $table->foreign('dette_id')->references('id')->on('dettes')->onUpdate('cascade')->onDelete('restrict');
            $table->decimal("versement", 15,2);
            $table->enum('paid_by', ['cash', 'check', 'orange money'])->default('cash');
            $table->string('notes');
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
        Schema::dropIfExists('payment_dettes');
    }
};
