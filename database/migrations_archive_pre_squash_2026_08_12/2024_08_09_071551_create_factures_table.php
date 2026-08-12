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
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->string("numero_facture")->unique();
            $table->bigInteger('customer_id')->unsigned();
            $table->foreign('customer_id')->references('id')->on('customers')->onUpdate('cascade')->onDelete('restrict');
            $table->bigInteger('store_id')->unsigned();
            $table->foreign('store_id')->references('id')->on('stores')->onUpdate('cascade')->onDelete('restrict');
            $table->integer('quantity')->nullable();
            $table->decimal('montant_total', 15,2)->nullable();
            $table->decimal("avance", 15,2);
            $table->decimal("reste", 15,2)->nullable();
            $table->enum('statut', ['non payé', 'partiel', 'payé'])->default('non payé');
            $table->enum('livraison', ['non livré', 'livré'])->default('non livré');
            $table->text("notes")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('factures');
    }
};
