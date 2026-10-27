<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Deux règles que seule une relecture complète du dossier des vues peut tenir,
 * et qu'aucun test fonctionnel n'attrape : elles portent sur du texte que
 * personne ne regarde tant qu'un client ne s'en plaint pas.
 *
 * 1. Aucun nom de client n'est écrit dans le code. Il l'était à quatre-vingts
 *    endroits — titres de pages, en-têtes de factures, e-mails, reçus, site
 *    public — et servait de valeur de repli ailleurs, ce qui revient au même
 *    dès qu'une vue est rendue sans qu'on lui passe la fiche entreprise.
 *
 * 2. Les vues rendues en PDF n'emploient pas de variable CSS. dompdf ne les
 *    résout pas : la couleur de marque y deviendrait transparente, en silence.
 *    C'est le genre de défaut qu'on ne voit qu'en ouvrant un PDF imprimé.
 */
class IdentiteSansClientEnDurTest extends TestCase
{
    /**
     * Noms des commerçants déjà livrés. Ils n'ont rien à faire dans le code :
     * la fiche entreprise les porte, et marqueNom() la lit.
     */
    private const NOMS_DE_CLIENTS = [
        // Noms
        'TELICO', 'COLEAH', 'BANDEYA',
        // Coordonnées. Le premier balayage ne cherchait que les noms : des
        // adresses et des numéros d'un autre commerçant sont restés dans le
        // pied du site public, la page contact, les reçus et un PDF — et
        // s'imprimaient tels quels chez le suivant.
        'Ecole Gare Routière', 'Dabola Camion',
        'abdulghadirybalde', 'Mamadoulawydiallo',
        '625 45 35 45', '628 59 31 10',
    ];

    /**
     * Les deux seuls endroits où un nom de client garde une raison d'être.
     * Ce n'est pas une liste où ranger un oubli : chaque entrée répond à une
     * contrainte technique, pas à une commodité.
     *
     * - config/brand.php : les valeurs par défaut sont celles de la première
     *   installation, pour que les bases déjà en service ne changent pas.
     * - BackupDatabase : la purge doit continuer à reconnaître les fichiers
     *   « coleah-*.sql » produits avant ce changement, sinon ils ne seraient
     *   plus jamais supprimés et le disque se remplirait en silence.
     */
    private const EXCEPTIONS = [
        'config/brand.php',
        'app/Console/Commands/BackupDatabase.php',
    ];

    /**
     * Vues rendues par dompdf (Pdf::loadView) et e-mails. Ni l'un ni l'autre ne
     * sait résoudre une variable CSS.
     */
    private const SANS_VARIABLES_CSS = [
        'devis/pdf', 'exports/sales-pdf', 'exports/ventes-tierces-pdf',
        'inventaires/feuille', 'inventaires/mode-operatoire', 'inventaires/rapport',
        'logistics/pdf', 'movements/pdf', 'payments/payments_pdf',
        'products/export', 'products/products-html', 'reports/daily-pdf',
    ];

    public function test_aucune_donnee_de_client_n_est_ecrite_dans_le_code(): void
    {
        $trouves = [];

        $aExaminer = iterator_to_array($this->fichiers([resource_path('views'), app_path(), config_path()]), false);
        // Le gabarit de déploiement : il portait le nom et la base du premier
        // client, et c'est le premier fichier que lit celui qui met en ligne.
        $aExaminer[] = base_path('.env.example');

        foreach ($aExaminer as $fichier) {
            $contenu = file_get_contents($fichier);

            $relatif = str_replace(base_path().'/', '', $fichier);

            if (in_array($relatif, self::EXCEPTIONS, true)) {
                continue;
            }

            foreach (self::NOMS_DE_CLIENTS as $nom) {
                if (stripos($contenu, $nom) !== false) {
                    $trouves[] = "$relatif → $nom";
                }
            }
        }

        $this->assertSame([], $trouves, "Donnée d'un client écrite en dur :\n  ".implode("\n  ", $trouves));
    }

    /**
     * dompdf ignore « var(--x) » et rend la propriété comme si elle n'était pas
     * là : un titre noir au lieu de la couleur de marque, une bordure invisible.
     * Ces vues doivent écrire la valeur, via marqueCouleur().
     */
    public function test_les_vues_pdf_n_emploient_pas_de_variable_css(): void
    {
        $fautives = [];

        foreach (self::SANS_VARIABLES_CSS as $vue) {
            $fichier = resource_path('views/'.$vue.'.blade.php');

            if (! file_exists($fichier)) {
                continue;
            }

            if (preg_match_all('/var\(\s*--[a-z0-9-]+/i', file_get_contents($fichier), $trouvees)) {
                $fautives[$vue] = count($trouvees[0]).' occurrence(s) : '.implode(', ', array_unique($trouvees[0]));
            }
        }

        $this->assertSame([], $fautives, "Variables CSS dans une vue PDF :\n  ".collect($fautives)
            ->map(fn ($detail, $vue) => "$vue — $detail")->implode("\n  "));
    }

    /**
     * Et l'inverse : une vue PDF doit bien appeler la couleur de marque, sinon
     * elle a simplement gardé ses valeurs en dur.
     */
    public function test_les_vues_pdf_colorees_passent_par_la_couleur_de_marque(): void
    {
        $sansMarque = [];

        foreach (self::SANS_VARIABLES_CSS as $vue) {
            $fichier = resource_path('views/'.$vue.'.blade.php');

            if (! file_exists($fichier)) {
                continue;
            }

            $contenu = file_get_contents($fichier);

            // Une vue qui n'emploie aucune des nuances de la marque n'a rien à prouver.
            $couleursDOrigine = ['#3E4C21', '#323D1A', '#1C230D', '#6B7A35', '#F4F5EC', '#E6E9D5'];
            $enDur = array_filter($couleursDOrigine, fn ($c) => stripos($contenu, $c) !== false);

            if ($enDur !== []) {
                $sansMarque[$vue] = implode(', ', $enDur);
            }
        }

        $this->assertSame([], $sansMarque, "Nuances de marque écrites en dur dans une vue PDF :\n  ".collect($sansMarque)
            ->map(fn ($couleurs, $vue) => "$vue — $couleurs")->implode("\n  "));
    }

    /** @return iterable<string> */
    private function fichiers(array $racines): iterable
    {
        foreach ($racines as $racine) {
            $iterateur = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine));

            foreach ($iterateur as $fichier) {
                if ($fichier->isFile() && preg_match('/\.(php|blade\.php)$/', $fichier->getFilename())) {
                    yield $fichier->getPathname();
                }
            }
        }
    }
}
