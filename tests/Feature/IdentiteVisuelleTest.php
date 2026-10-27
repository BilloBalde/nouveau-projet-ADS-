<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Support\IdentiteVisuelle;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Le projet est revendu à plusieurs commerçants. Habiller une installation
 * demandait jusqu'ici un rechercher-remplacer dans la feuille du thème et dans
 * les vues — et le nom « TELICO » restait écrit en dur dans l'en-tête, le
 * favicon et toute la page de connexion, dont le pied de page qui affichait
 * l'adresse d'un autre client.
 *
 * Deux exigences, et la seconde compte autant que la première :
 *  - une ligne de .env suffit à rhabiller l'application ;
 *  - sans cette ligne, les installations déjà livrées rendent exactement la
 *    même chose qu'avant.
 */
class IdentiteVisuelleTest extends TestCase
{
    use RefreshDatabase;

    /** Palette d'origine, valeur par valeur. Ce tableau est le contrat. */
    private const ORIGINE = [
        '50' => '#F4F5EC', '100' => '#E6E9D5', '200' => '#CED5AE', '300' => '#ADB97E',
        '400' => '#8C9A55', '500' => '#6B7A35', '600' => '#3E4C21', '700' => '#323D1A',
        '800' => '#283114', '900' => '#1C230D', '950' => '#10150A',
    ];

    private function admin(): User
    {
        $this->seed(RolesSeeder::class);
        $this->seed(PermissionSeeder::class);

        return User::create([
            'name' => 'Patron', 'email' => 'patron@example.com', 'username' => 'patron',
            'password' => Hash::make('password'), 'role_id' => User::ROLE_ADMIN,
            'phone' => '620', 'status' => 'Active', 'token' => '',
        ])->fresh();
    }

    private function entreprise(array $extra = []): Company
    {
        return Company::create($extra + [
            'name' => 'THB BANTIGUEL',
            'address' => 'Boutique 22, Madina Marché, Conakry',
            'email' => 'contact@example.com',
            'phone' => '628 263 636',
            'about' => 'Commerce de détail.',
        ]);
    }

    /** Contraste WCAG d'une couleur sur fond blanc. */
    private function contrasteSurBlanc(string $hexa): float
    {
        $canal = function (int $valeur): float {
            $c = $valeur / 255;

            return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        $luminance = 0.2126 * $canal((int) hexdec(substr($hexa, 1, 2)))
            + 0.7152 * $canal((int) hexdec(substr($hexa, 3, 2)))
            + 0.0722 * $canal((int) hexdec(substr($hexa, 5, 2)));

        return 1.05 / ($luminance + 0.05);
    }

    // -----------------------------------------------------------------
    // Les clients déjà livrés ne bougent pas
    // -----------------------------------------------------------------

    public function test_sans_reglage_la_palette_d_origine_est_rendue_a_l_identique(): void
    {
        config(['brand.couleur' => null]);

        $this->assertSame(self::ORIGINE, IdentiteVisuelle::rampe());
    }

    /**
     * Le piège du fichier .env : un « # » non protégé y ouvre un commentaire,
     * et la valeur arrive vide. Elle doit retomber sur la palette d'origine,
     * jamais produire des couleurs au hasard.
     */
    public function test_une_valeur_inutilisable_retombe_sur_la_palette_d_origine(): void
    {
        foreach (['', '   ', 'violet', '#GGGGGG', '#54', '543FC2AA'] as $saisie) {
            config(['brand.couleur' => $saisie]);
            $this->assertSame(self::ORIGINE, IdentiteVisuelle::rampe(), "Refusé attendu pour « $saisie ».");
        }
    }

    // -----------------------------------------------------------------
    // Une ligne de .env suffit
    // -----------------------------------------------------------------

    /**
     * Ce qu'on écrit dans .env est ce qu'on voit. La rampe était d'abord bâtie
     * sur des luminosités absolues : la nuance demandée n'était rendue que si
     * la sienne tombait par hasard sur le palier du ton 600 — ce qui était le
     * cas du violet retenu, et masquait l'écart pour toutes les autres.
     */
    public function test_la_nuance_demandee_est_rendue_exactement(): void
    {
        foreach (['#543FC2', '#B3261E', '#0B6E4F', '#1F6FEB', '#C7A150', '#8C9A55'] as $couleur) {
            config(['brand.couleur' => $couleur]);
            $this->assertSame($couleur, IdentiteVisuelle::ton('600'), "Nuance $couleur déformée.");
        }

        // Le dièse est facultatif : c'est l'écriture qui évite les guillemets
        // dans .env.
        config(['brand.couleur' => '543FC2']);
        $this->assertSame('#543FC2', IdentiteVisuelle::ton('600'));
    }

    public function test_la_rampe_va_du_plus_clair_au_plus_sombre(): void
    {
        config(['brand.couleur' => '#543FC2']);
        $precedent = null;

        foreach (IdentiteVisuelle::rampe() as $ton => $hexa) {
            $contraste = $this->contrasteSurBlanc($hexa);
            if ($precedent !== null) {
                $this->assertGreaterThan($precedent, $contraste, "Le ton $ton devrait être plus sombre que le précédent.");
            }
            $precedent = $contraste;
        }
    }

    /**
     * Les tons plus sombres que la nuance choisie ne peuvent jamais contraster
     * moins qu'elle. C'est ce qui rend le réglage sûr : si la couleur retenue
     * est lisible — bouton blanc sur fond de marque, nom de l'entreprise sur
     * blanc —, tout ce qui en descend l'est aussi.
     *
     * L'inverse n'est pas promis : une couleur claire, un or par exemple, reste
     * illisible, et aucune rampe n'y changera rien. C'est au choix de la
     * couleur de le régler, pas au code.
     */
    public function test_les_tons_plus_sombres_contrastent_au_moins_autant_que_la_nuance_choisie(): void
    {
        foreach (['#543FC2', '#B3261E', '#0B6E4F', '#1F6FEB', '#C7A150', '#7A7A7A'] as $couleur) {
            config(['brand.couleur' => $couleur]);
            $rampe = IdentiteVisuelle::rampe();
            $reference = $this->contrasteSurBlanc($rampe['600']);

            foreach (['700', '800', '900', '950'] as $ton) {
                $this->assertGreaterThanOrEqual(
                    round($reference, 2),
                    round($this->contrasteSurBlanc($rampe[$ton]), 2),
                    "Couleur $couleur, ton $ton : plus sombre mais moins contrasté."
                );
            }
        }
    }

    /**
     * Et pour une couleur elle-même lisible, toute la moitié sombre l'est.
     */
    public function test_une_couleur_lisible_donne_une_moitie_sombre_lisible(): void
    {
        foreach (['#543FC2', '#B3261E', '#0B6E4F', '#1F6FEB'] as $couleur) {
            config(['brand.couleur' => $couleur]);
            $rampe = IdentiteVisuelle::rampe();

            $this->assertGreaterThanOrEqual(4.5, round($this->contrasteSurBlanc($rampe['600']), 2),
                "La couleur témoin $couleur devrait être lisible sur blanc.");

            foreach (['600', '700', '800', '900', '950'] as $ton) {
                $this->assertGreaterThanOrEqual(
                    4.5,
                    round($this->contrasteSurBlanc($rampe[$ton]), 2),
                    "Couleur $couleur, ton $ton : contraste insuffisant sur blanc."
                );
            }
        }
    }

    // -----------------------------------------------------------------
    // Ce que voit l'utilisateur
    // -----------------------------------------------------------------

    public function test_la_couleur_choisie_arrive_bien_dans_la_page(): void
    {
        config(['brand.couleur' => '#543FC2']);
        $this->entreprise();

        $this->actingAs($this->admin())->get(route('home'))->assertOk()
            ->assertSee('--adm-brand-600: #543FC2;', false)
            ->assertDontSee('--adm-brand-600: #3E4C21;', false);
    }

    public function test_l_en_tete_porte_le_nom_de_l_entreprise_et_plus_celui_d_un_autre_client(): void
    {
        $this->entreprise();

        $page = $this->actingAs($this->admin())->get(route('home'))->assertOk();
        $page->assertSee('THB BANTIGUEL');
        $page->assertDontSee('TELICO');
    }

    public function test_la_page_de_connexion_porte_le_nom_et_l_adresse_de_l_entreprise(): void
    {
        $this->entreprise();

        $this->get(route('login'))->assertOk()
            ->assertSee('THB BANTIGUEL')
            ->assertSee('Boutique 22, Madina Marché, Conakry')
            ->assertDontSee('TELICO')
            ->assertDontSee('Madina, Ecole Gare Routière Dabola Camion');
    }

    /**
     * Sans logo téléversé, l'emblème livré avec le thème — celui d'un autre
     * client — s'affichait en haut de chaque page. Un monogramme le remplace.
     */
    public function test_sans_logo_le_monogramme_remplace_l_embleme_d_un_autre_client(): void
    {
        $entreprise = $this->entreprise();
        $this->assertSame('TB', $entreprise->initiales());

        $page = $this->actingAs($this->admin())->get(route('home'))->assertOk();
        $page->assertSee('logo-monogramme', false);
        $page->assertDontSee('companies/telico.png');
    }

    public function test_avec_un_logo_c_est_le_logo_qui_s_affiche(): void
    {
        $this->entreprise(['logo' => 'thb.png']);

        $page = $this->actingAs($this->admin())->get(route('home'))->assertOk();
        $page->assertSee('companies/thb.png', false);
        $page->assertDontSee('logo-monogramme', false);
    }

    /**
     * Le repli des vignettes de compte pointait sur le logo de l'entreprise.
     * Sans logo, il est devenu nul : « src="" » ne laisse pas l'image vide, le
     * navigateur recharge la page courante à la place — trois fois par écran.
     */
    public function test_aucune_image_ne_pointe_dans_le_vide(): void
    {
        $this->entreprise();

        $page = $this->actingAs($this->admin())->get(route('home'))->assertOk();

        $this->assertStringNotContainsString('src=""', $page->getContent(), 'Une image sans source recharge la page entière.');
        $this->assertStringNotContainsString("src=''", $page->getContent());
    }

    public function test_les_initiales_tiennent_sur_un_nom_d_un_seul_mot(): void
    {
        $this->assertSame('TE', (new Company(['name' => 'TELICO']))->initiales());
        $this->assertSame('CB', (new Company(['name' => 'COLEAH BANDEYA & FILS']))->initiales());
        $this->assertSame('?', (new Company(['name' => '']))->initiales());
    }
}
