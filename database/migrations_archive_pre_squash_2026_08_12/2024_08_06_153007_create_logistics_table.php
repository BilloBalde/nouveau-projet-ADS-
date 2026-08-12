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
        Schema::create('logistics', function (Blueprint $table) {
            $table->id();
            $table->string('numeroPurchase')->unique();
            $table->string('typeLogistic')->default('conteneur');
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->integer('quantity');
            $table->double('depense', 20, 2);
            $table->date('dateEmis');
            $table->date('dateFournis');
            $table->string('filePurchase')->nullable();
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
        Schema::dropIfExists('logistics');
    }
};
