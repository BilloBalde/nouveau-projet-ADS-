<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Support\IdentiteVisuelle;
use Database\Seeders\CompanySeeder;
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

    /**
     * Le seeder inscrivait « default.png » comme photo de profil des comptes
     * créés, mais ce fichier n'a jamais été livré : trois images cassées sur
     * chaque page, sur toutes les installations. Le nom enregistré ne suffit
     * pas, il faut que le fichier existe.
     */
    public function test_une_photo_de_profil_absente_ne_casse_pas_la_vignette(): void
    {
        $this->entreprise();
        $admin = $this->admin();
        $admin->forceFill(['profilePic' => 'fichier-absent.png'])->save();

        $page = $this->actingAs($admin)->get(route('home'))->assertOk();

        $page->assertDontSee('avatars/fichier-absent.png');
        $this->assertStringNotContainsString('src=""', $page->getContent());
    }

    // -----------------------------------------------------------------
    // Reprendre une installation déjà en service
    // -----------------------------------------------------------------

    /**
     * CompanySeeder ne sert que les bases neuves. Sur une base reprise — ou
     * copiée d'un autre client — la fiche enregistrée reste celle d'avant, et
     * c'est elle que portent l'en-tête, les factures et les PDF : renseigner
     * .env n'y change rien tant que personne ne l'applique.
     */
    public function test_la_commande_remplace_la_fiche_existante_sans_en_creer_une_seconde(): void
    {
        $ancienne = $this->entreprise(['name' => 'TELICO', 'address' => 'Ailleurs', 'logo' => 'ancien.png']);

        config(['brand.entreprise_renseignee' => true, 'brand.entreprise' => [
            'name' => 'THB BANTIGUEL',
            'address' => 'Boutique 22, Madina Marché, Conakry',
            'email' => 'contact@example.com',
            'phone' => '628 263 636',
            'phone_secondary' => '666 321 811',
            'about' => 'Commerce de détail.',
            'logo' => null,
        ]]);

        $this->artisan('identite:appliquer', ['--force' => true])->assertSuccessful();

        $this->assertSame(1, Company::count(), 'Une seconde fiche a été créée : Company::current() deviendrait imprévisible.');

        $fiche = Company::current();
        $this->assertSame('THB BANTIGUEL', $fiche->name);
        $this->assertSame($ancienne->id, $fiche->id, 'La fiche existante devait être modifiée, pas remplacée.');
        $this->assertNull($fiche->logo, 'Le logo de l\'ancien client devait être retiré.');
    }

    /**
     * Le danger réel de cette commande : sans COMPANY_* dans .env,
     * config/brand.php rend les valeurs de repli — celles de la première
     * installation. La commande écraserait alors la fiche du client avec
     * l'identité d'un autre commerçant, et le tableau avant/après ne le dirait
     * qu'une fois la modification faite. Elle doit refuser, --force compris.
     */
    public function test_la_commande_refuse_d_appliquer_les_valeurs_de_repli(): void
    {
        $this->entreprise(['name' => 'TELICO']);

        config([
            'brand.entreprise_renseignee' => false,
            'brand.entreprise' => [
                'name' => 'COLEAH BANDEYA & FILS', 'address' => 'Madina', 'email' => null,
                'phone' => null, 'phone_secondary' => null, 'about' => 'x', 'logo' => null,
            ],
        ]);

        $this->artisan('identite:appliquer', ['--force' => true])->assertFailed();

        $this->assertSame('TELICO', Company::current()->name,
            'La fiche a été écrasée avec l\'identité d\'un autre client.');
    }

    public function test_la_commande_refuse_d_agir_sans_nom(): void
    {
        $this->entreprise(['name' => 'TELICO']);
        config(['brand.entreprise_renseignee' => true, 'brand.entreprise' => ['name' => '', 'address' => 'x']]);

        $this->artisan('identite:appliquer', ['--force' => true])->assertFailed();

        $this->assertSame('TELICO', Company::current()->name, 'La fiche ne devait pas être touchée.');
    }

    /**
     * Le seeder repérait la fiche par son nom : changer COMPANY_NAME pour un
     * nouveau client en ajoutait une seconde au lieu de remplacer la première.
     */
    public function test_le_seeder_ne_cree_pas_une_seconde_fiche_quand_le_nom_change(): void
    {
        $this->entreprise(['name' => 'TELICO']);

        config(['brand.entreprise_renseignee' => true, 'brand.entreprise' => [
            'name' => 'THB BANTIGUEL', 'address' => 'Madina', 'email' => null,
            'phone' => null, 'phone_secondary' => null, 'about' => 'x', 'logo' => null,
        ]]);

        $this->seed(CompanySeeder::class);

        $this->assertSame(1, Company::count());
        $this->assertSame('THB BANTIGUEL', Company::current()->name);
    }

    public function test_les_initiales_tiennent_sur_un_nom_d_un_seul_mot(): void
    {
        $this->assertSame('TE', (new Company(['name' => 'TELICO']))->initiales());
        $this->assertSame('CB', (new Company(['name' => 'COLEAH BANDEYA & FILS']))->initiales());
        $this->assertSame('?', (new Company(['name' => '']))->initiales());
    }
}
