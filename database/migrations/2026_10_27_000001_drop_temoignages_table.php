<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La table des témoignages n'a jamais servi : aucun écran ne l'affichait,
 * aucun contrôleur routé ne l'alimentait, et elle est restée vide sur toutes
 * les installations. Son modèle et son contrôleur sont retirés avec elle.
 *
 * dropIfExists : les installations neuves ne la créent plus, cette migration
 * ne doit pas échouer pour autant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('temoignages');
    }

    public function down(): void
    {
        Schema::create('temoignages', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }
};
