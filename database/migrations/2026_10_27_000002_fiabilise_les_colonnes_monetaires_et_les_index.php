<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deux corrections de schéma relevées à l'audit.
 *
 * 1. Montants d'une facture acceptant NULL sans valeur par défaut.
 *    montant_total, reste et quantity pouvaient valoir NULL. Un NULL ne vaut
 *    pas zéro : il se propage, et « SUM(montant_total) » sur une seule facture
 *    à NULL rend NULL — un total affiché vide plutôt que faux, donc jamais
 *    remarqué. Une facture a toujours un montant : ces trois colonnes passent
 *    à NOT NULL avec 0 par défaut.
 *
 *    sales.unit_cost et purchases.price_ctn restent nullables, à dessein : le
 *    NULL y signifie « coût inconnu », ce qui n'est pas « coût nul ». Les
 *    passer à 0 ferait apparaître la marge d'une vente au prix de vente entier.
 *    La tentative a d'ailleurs cassé dix-huit tests, Devi.php écrivant
 *    explicitement null dans unit_cost quand la vente ne vient pas d'un fonds.
 *
 * 2. Index manquants sur les colonnes de date.
 *    Les rapports filtrent factures, achats et dépenses sur created_at, et les
 *    comptages sur leur statut. Sans index, chaque rapport relit la table
 *    entière — invisible sur une base de démonstration, pas sur deux ans de
 *    ventes.
 */
return new class extends Migration
{
    /** @var array<string, array<int, string>> */
    private const MONTANTS = [
        'factures' => ['montant_total', 'reste'],
    ];

    /** @var array<string, string> */
    private const INDEX = [
        'factures' => 'created_at',
        'purchases' => 'created_at',
        'expenses' => 'created_at',
        'inventory_sessions' => 'status',
    ];

    public function up(): void
    {
        foreach (self::MONTANTS as $table => $colonnes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($colonnes as $colonne) {
                if (! Schema::hasColumn($table, $colonne)) {
                    continue;
                }

                // Un montant absent valait zéro dans tous les calculs du
                // projet : on l'écrit, plutôt que de le laisser implicite.
                DB::table($table)->whereNull($colonne)->update([$colonne => 0]);
            }

            Schema::table($table, function (Blueprint $t) use ($table, $colonnes) {
                foreach ($colonnes as $colonne) {
                    if (Schema::hasColumn($table, $colonne)) {
                        $t->decimal($colonne, 15, 2)->default(0)->nullable(false)->change();
                    }
                }
            });
        }

        if (Schema::hasTable('factures') && Schema::hasColumn('factures', 'quantity')) {
            DB::table('factures')->whereNull('quantity')->update(['quantity' => 0]);
            Schema::table('factures', function (Blueprint $t) {
                $t->integer('quantity')->default(0)->nullable(false)->change();
            });
        }

        foreach (self::INDEX as $table => $colonne) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $colonne)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table, $colonne) {
                // Nom explicite : sans lui, deux migrations peuvent produire le
                // même nom automatique et la seconde échoue.
                $t->index($colonne, "{$table}_{$colonne}_idx");
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEX as $table => $colonne) {
            if (Schema::hasTable($table)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex("{$table}_{$colonne}_idx"));
            }
        }

        foreach (self::MONTANTS as $table => $colonnes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table, $colonnes) {
                foreach ($colonnes as $colonne) {
                    if (Schema::hasColumn($table, $colonne)) {
                        $t->decimal($colonne, 15, 2)->nullable()->change();
                    }
                }
            });
        }

        if (Schema::hasTable('factures')) {
            Schema::table('factures', fn (Blueprint $t) => $t->integer('quantity')->nullable()->change());
        }
    }
};
