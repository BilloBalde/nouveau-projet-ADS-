<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Devi::validate() passe le devis au statut « validated », que les vues
 * affichent déjà (badge « Validé »), mais que l'énumération ne contenait pas :
 * la validation échouait systématiquement sur MySQL en mode strict.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devis', function (Blueprint $table) {
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired', 'validated'])
                ->default('draft')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('devis', function (Blueprint $table) {
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])
                ->default('draft')
                ->change();
        });
    }
};
