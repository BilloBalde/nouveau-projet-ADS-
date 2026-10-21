<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->constrained('factures')->onDelete('restrict')->onUpdate('cascade');
            $table->string('receipt_number')->nullable();
            $table->decimal('versement', 15, 2);
            $table->decimal('total_paye', 15, 2);
            $table->decimal('reste', 15, 2);
            $table->enum('paid_by', ['cash', 'check', 'orange money'])->default('cash');
            $table->string('note');
            $table->timestamps();
            $table->unique('receipt_number', 'payments_receipt_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
