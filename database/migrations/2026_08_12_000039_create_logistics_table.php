<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics', function (Blueprint $table) {
            $table->id();
            $table->string('numeroPurchase');
            $table->string('typeLogistic')->default('conteneur');
            $table->foreignId('store_id')->constrained('stores')->onDelete('restrict');
            $table->integer('quantity');
            $table->double('depense');
            $table->date('dateEmis');
            $table->date('dateFournis');
            $table->string('filePurchase')->nullable();
            $table->timestamps();
            $table->unique('numeroPurchase', 'logistics_numeropurchase_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics');
    }
};
