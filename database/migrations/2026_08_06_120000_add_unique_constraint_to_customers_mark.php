<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La migration d'origine (2024_07_28_075653_create_customers_table) voulait rendre
     * `mark` unique mais utilisait `->unique;` (accès à une propriété) au lieu de
     * `->unique()` (appel de méthode) : la contrainte n'a jamais existé en base.
     */
    public function up(): void
    {
        // Idempotent : sur une base où create_customers_table a déjà été recréée depuis
        // le correctif de la coquille (->unique() au lieu de ->unique;), l'index existe
        // déjà et cette migration n'a plus rien à faire. "SHOW INDEX" est une syntaxe
        // MySQL — sur les autres connexions (ex : sqlite en mémoire pour les tests),
        // la base est toujours neuve, donc pas besoin de vérifier.
        if (DB::connection()->getDriverName() === 'mysql') {
            $alreadyExists = collect(DB::select("SHOW INDEX FROM customers WHERE Key_name = 'customers_mark_unique'"))->isNotEmpty();

            if ($alreadyExists) {
                return;
            }
        }

        $duplicates = DB::table('customers')
            ->select('mark')
            ->whereNotNull('mark')
            ->groupBy('mark')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('mark');

        if ($duplicates->isNotEmpty()) {
            throw new \RuntimeException(
                "Impossible d'ajouter la contrainte unique sur customers.mark : valeurs en double détectées ("
                . $duplicates->implode(', ')
                . '). Dédupliquez ces clients avant de relancer cette migration.'
            );
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('mark');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['mark']);
        });
    }
};
