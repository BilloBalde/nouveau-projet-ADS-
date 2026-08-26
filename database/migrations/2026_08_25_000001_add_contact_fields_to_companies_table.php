<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L'e-mail et le téléphone de l'entreprise s'affichent en pied de chaque facture,
 * devis, bon de commande et bon de sortie — mais la table ne les portait pas. Le
 * code écrivait donc partout `$company?->email ?? 'une.adresse@exemple.com'`, et
 * comme la propriété n'existait pas, c'est le repli codé en dur qui s'imprimait
 * sur tous les documents remis aux clients, sans moyen de le changer depuis
 * l'interface.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('email')->nullable()->after('address');
            $table->string('phone')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone']);
        });
    }
};
