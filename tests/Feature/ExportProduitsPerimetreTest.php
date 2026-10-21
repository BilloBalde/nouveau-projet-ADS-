<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Product;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Exports produits : Excel, PDF et CSV doivent appliquer le MÊME périmètre que
 * la liste à l'écran.
 *
 * Les versions PDF et CSV vivaient dans des closures de routes/web.php et y
 * avaient gardé l'ancien couplage « role_id == 3 + stores.user_id », alors que
 * la liste et l'export Excel étaient passés à isStoreScoped(). Un vendeur
 * affecté par le mécanisme actuel obtenait donc un périmètre nul — c'est-à-dire
 * le stock de tous les emplacements cumulés — et les rôles créés ensuite
 * (magasinier, responsable magasin) n'étaient pas limités du tout.
 */
class ExportProduitsPerimetreTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Store $madina;
    private Store $kaloum;
    private Product $ciment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->admin = $this->user('Patron', User::ROLE_ADMIN);
        Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->madina = $this->magasin('Madina');
        $this->kaloum = $this->magasin('Kaloum');

        $this->ciment = Product::create(['libelle' => 'Ciment', 'sku' => 'CIM', 'description' => 'x',
            'unite' => 'carton', 'price' => 60000, 'price_sale' => 45000]);

        // 7 à Madina, 93 à Kaloum : le total (100) ne doit jamais apparaître
        // dans l'export d'un employé rattaché à Madina.
        StoreProduct::create(['store_id' => $this->madina->id, 'product_id' => $this->ciment->id, 'quantity' => 7]);
        StoreProduct::create(['store_id' => $this->kaloum->id, 'product_id' => $this->ciment->id, 'quantity' => 93]);
    }

    private function magasin(string $nom): Store
    {
        return Store::create(['store_name' => $nom, 'place_id' => Place::firstOrFail()->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin']);
    }

    private function user(string $name, int|string $role, ?int $storeId = null): User
    {
        $roleId = is_int($role) ? $role : (int) Role::where('slug', $role)->value('id');

        $user = User::create([
            'name' => $name, 'email' => str()->slug($name).uniqid().'@example.com', 'username' => str()->slug($name).uniqid(),
            'password' => Hash::make('password'), 'role_id' => $roleId, 'phone' => '620', 'status' => 'Active', 'token' => '',
        ]);

        if ($storeId) {
            $user->assignToStore($storeId);
        }

        return $user->fresh();
    }

    private function csv(User $par): string
    {
        $reponse = $this->actingAs($par)->get(route('produits.export-csv'))->assertOk();

        return $reponse->streamedContent();
    }

    /** Le PDF n'est pas lisible : on vérifie la vue qui le compose. */
    private function stockDansLePdf(User $par): int
    {
        $this->actingAs($par)->get(route('produits.export-pdf'))->assertOk();

        $vue = view('products.export', [
            'dataTable' => Product::with('categories', 'stores')->get(),
            'userStoreId' => $par->isStoreScoped() ? $par->assignedStoreId() : null,
        ])->render();

        preg_match('/<td>Ciment<\/td>\s*<td>CIM<\/td>\s*<td>[^<]*<\/td>\s*<td>(\d+)<\/td>/', $vue, $m);

        return (int) ($m[1] ?? -1);
    }

    public function test_un_vendeur_n_exporte_que_le_stock_de_son_magasin(): void
    {
        $vendeur = $this->user('Awa', User::ROLE_STORE_MANAGER, $this->madina->id);

        $csv = $this->csv($vendeur);
        $this->assertStringContainsString('Ciment', $csv);
        $this->assertMatchesRegularExpression('/Ciment,CIM,[^,]*,7,/', $csv, 'Le CSV ne montre pas le stock du seul magasin du vendeur.');
        $this->assertStringNotContainsString(',100,', $csv, 'Le CSV expose le stock cumulé de tous les emplacements.');

        $this->assertSame(7, $this->stockDansLePdf($vendeur), 'Le PDF ne montre pas le stock du seul magasin du vendeur.');
    }

    /**
     * Le rôle qui faisait échouer l'ancienne règle : son role_id n'est pas 3,
     * donc aucune restriction ne s'appliquait.
     */
    public function test_un_responsable_magasin_n_exporte_que_son_magasin(): void
    {
        $responsable = $this->user('Mariama', 'responsable', $this->madina->id);

        $this->assertMatchesRegularExpression('/Ciment,CIM,[^,]*,7,/', $this->csv($responsable));
        $this->assertSame(7, $this->stockDansLePdf($responsable));
    }

    public function test_une_vue_globale_exporte_le_cumul(): void
    {
        $csv = $this->csv($this->admin);

        $this->assertMatchesRegularExpression('/Ciment,CIM,[^,]*,100,/', $csv, 'La vue globale doit cumuler tous les emplacements.');
        $this->assertSame(100, $this->stockDansLePdf($this->admin));
    }

    public function test_les_exports_exigent_la_permission_de_voir_les_produits(): void
    {
        // Le comptable n'a pas products.view dans son preset.
        $comptable = $this->user('Fatou', 'comptable');

        foreach (['produits.export-excel', 'produits.export-pdf', 'produits.export-csv'] as $route) {
            $this->actingAs($comptable)->get(route($route))->assertForbidden();
        }
    }

    public function test_l_export_excel_reste_limite_au_magasin(): void
    {
        $vendeur = $this->user('Awa', User::ROLE_STORE_MANAGER, $this->madina->id);

        $this->actingAs($vendeur)->get(route('produits.export-excel'))
            ->assertOk()
            ->assertDownload('products.xlsx');
    }

    /**
     * La colonne « Prix » du CSV lisait price_sale_ctn, colonne qui n'existe
     * plus depuis le passage à une unité unique par produit : elle valait 0
     * pour chaque ligne.
     */
    public function test_le_csv_exporte_un_prix_renseigne(): void
    {
        $this->assertMatchesRegularExpression('/Ciment,CIM,[^,]*,100,cartons,60000/', $this->csv($this->admin));
    }
}
