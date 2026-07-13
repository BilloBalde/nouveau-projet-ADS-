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
        Schema::create('paiement_journaliers', function (Blueprint $table) {
            $table->id();
           $table->string('reference');
           $table->bigInteger('journalier_id')->unsigned();
           $table->foreign('journalier_id')->references('id')->on('journaliers')->onUpdate('cascade')->onDelete('restrict');
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
        Schema::dropIfExists('paiement_journaliers');
    }
};
