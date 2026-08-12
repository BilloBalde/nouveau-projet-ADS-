<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journaliers', function (Blueprint $table) {
            $table->id();
            $table->string('nomPrenant');
            $table->double('montant');
            $table->text('contenu');
            $table->double('paid')->default(0);
            $table->double('reste');
            $table->date('datePrise');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journaliers');
    }
};
