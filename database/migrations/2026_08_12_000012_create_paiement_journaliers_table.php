<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiement_journaliers', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->foreignId('journalier_id')->constrained('journaliers')->onDelete('restrict')->onUpdate('cascade');
            $table->decimal('versement', 15, 2);
            $table->enum('paid_by', ['cash', 'check', 'orange money'])->default('cash');
            $table->string('notes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiement_journaliers');
    }
};
