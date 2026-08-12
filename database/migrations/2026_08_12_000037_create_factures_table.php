<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->string('numero_facture');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('store_id')->constrained('stores')->onDelete('restrict')->onUpdate('cascade');
            $table->integer('quantity')->nullable();
            $table->decimal('montant_total', 15, 2)->nullable();
            $table->decimal('avance', 15, 2);
            $table->decimal('reste', 15, 2)->nullable();
            $table->enum('statut', ['non payé','partiel','payé'])->default('non payé');
            $table->enum('livraison', ['non livré','partiellement livré','livré'])->default('non livré');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('numero_facture', 'factures_numero_facture_index');
            $table->unique('numero_facture', 'factures_numero_facture_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
