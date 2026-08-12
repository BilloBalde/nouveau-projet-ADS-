<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Le nom et le logo de l'entreprise sont lus depuis la table companies
     * (en-tête admin, titre de l'onglet, tous les PDF). L'ancien enregistrement
     * pointait encore vers l'ancienne marque et un fichier logo.png supprimé.
     */
    public function up(): void
    {
        DB::table('companies')->where('id', 1)->update([
            'name' => 'SMH',
            'logo' => 'logo.jpg',
        ]);
    }

    public function down(): void
    {
        DB::table('companies')->where('id', 1)->update([
            'name' => 'EDAAG TRADING',
            'logo' => 'logo.png',
        ]);
    }
};
