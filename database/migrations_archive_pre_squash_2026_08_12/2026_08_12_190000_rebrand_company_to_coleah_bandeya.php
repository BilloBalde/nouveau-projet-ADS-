<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Nouveau client : Coleah Bandeya & Fils (quincaillerie, Conakry).
     * Voir 2026_07_12_052436_rebrand_company_to_smh.php pour le rebranding précédent.
     */
    public function up(): void
    {
        DB::table('companies')->where('id', 1)->update([
            'name' => 'COLEAH BANDEYA & FILS',
            'address' => 'Madina, Ecole Gare Routière Dabola Camion, Conakry',
            'logo' => 'ADK.jpg',
            'about' => 'Quincaillerie générale : peinture, baguette, pelle, pioche, serrure et divers.',
        ]);
    }

    public function down(): void
    {
        DB::table('companies')->where('id', 1)->update([
            'name' => 'SMH',
            'address' => 'Kobaya, C/Ratoma, Conakry',
            'logo' => 'logo.jpg',
            'about' => 'Le manager principale du site, il est chargé de la gestion complète et mis à jour du site',
        ]);
    }
};
