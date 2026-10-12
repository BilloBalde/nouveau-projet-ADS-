<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Place;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Unités de vente : pièce, douzaine, carton (n pièces), paquet (n pièces),
 * rouleau (dimension). Le stock reste compté dans l'unité de base.
 */
class SaleUnitTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Store $magasin;
    private Customer $client;
    private Category $categorie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->admin = User::create([
            'name' => 'Patron', 'email' => 'patron@example.com', 'username' => 'patron',
            'password' => Hash::make('password'), 'role_id' => User::ROLE_ADMIN,
            'phone' => '620', 'status' => 'Active', 'token' => '',
        ]);
        $lieu = Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->magasin = Store::create(['store_name' => 'Madina', 'place_id' => $lieu->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin', 'is_main' => true]);
        $this->client = Customer::create(['customerName' => 'Client X', 'mark' => 'CLX', 'tel' => '620', 'address' => 'Madina']);
        $this->categorie = Category::create(['slug' => 'quincaillerie', 'category_type' => 'Quincaillerie', 'description' => 'x']);
    }

    private function creerProduit(array $unites): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->post(route('produits.store'), [
            'categories' => [$this->categorie->id], 'libelle' => 'Vis 6 mm', 'sku' => 'VIS-' . uniqid(),
            'description' => 'x', 'price_sale' => 100, 'stock_initial' => 500, 'store_id' => $this->magasin->id,
        ] + $unites);
    }

    private function vendre(Product $produit, int $qte, string $unite, float $prix)
    {
        return $this->actingAs($this->admin)->post(route('sales.store'), [
            'numeroFacture' => 'F-' . uniqid(), 'store_id' => $this->magasin->id, 'customer_id' => $this->client->id,
            'avance' => 0, 'paid_by' => 'cash',
            'sales' => [1 => ['product_id' => $produit->id, 'quantity' => $qte, 'unite' => $unite, 'prix' => $prix,
                'store_id' => $this->magasin->id, 'is_third_party' => '0']],
        ]);
    }

    public function test_creation_avec_carton_paquet_et_douzaine(): void
    {
        $this->creerProduit(['unites_vente' => ['pcs', 'douzaine', 'carton', 'paquet'], 'pcs_par_carton' => 24, 'pcs_par_paquet' => 10])
            ->assertSessionHasNoErrors()->assertRedirect(route('produits.index'));

        $p = Product::firstOrFail();
        $this->assertSame(['pcs', 'douzaine', 'carton', 'paquet'], $p->codesUnites());
        $this->assertSame(24, $p->facteurUnite('carton'));
        $this->assertSame(10, $p->facteurUnite('paquet'));
        $this->assertSame(12, $p->facteurUnite('douzaine'));
        $this->assertNull($p->facteurUnite('rouleau'));
        $this->assertSame('Carton (24 pcs)', $p->unitesDisponibles()['carton']['libelle']);
    }

    public function test_le_nombre_de_pieces_et_la_dimension_sont_obligatoires(): void
    {
        $this->creerProduit(['unites_vente' => ['carton']])->assertSessionHasErrors('pcs_par_carton');
        $this->creerProduit(['unites_vente' => ['paquet']])->assertSessionHasErrors('pcs_par_paquet');
        $this->creerProduit(['unites_vente' => ['rouleau']])->assertSessionHasErrors('dimension_rouleau');
        $this->creerProduit(['unites_vente' => ['rouleau', 'pcs'], 'dimension_rouleau' => '100 m'])->assertSessionHasErrors('unites_vente');
        $this->assertSame(0, Product::count());

        $this->creerProduit(['unites_vente' => ['rouleau'], 'dimension_rouleau' => '100 m'])->assertSessionHasNoErrors();
        $this->assertSame('Rouleau (100 m)', Product::firstOrFail()->unitesDisponibles()['rouleau']['libelle']);
    }

    public function test_vente_au_carton_sort_les_pieces_du_stock(): void
    {
        $this->creerProduit(['unites_vente' => ['pcs', 'carton'], 'pcs_par_carton' => 24]);
        $p = Product::firstOrFail();

        // 2 cartons de 24 à 3 000 le carton (125 la pièce, coût 100).
        $this->vendre($p, 2, 'carton', 3000)->assertSessionHas('success');

        $this->assertSame(500 - 48, (int) StoreProduct::where('product_id', $p->id)->value('quantity'));
        $vente = Sale::firstOrFail();
        $this->assertSame(48, (int) $vente->quantity);
        $this->assertSame('carton', $vente->unite);
        $this->assertSame(2, (int) $vente->quantite_unite);
        $this->assertEquals(6000, $vente->prixTotal);
        $this->assertEquals(125, $vente->prix);
        $this->assertEquals(6000 - 48 * 100, $vente->interet);
        $this->assertSame('2 cartons (48 pcs)', $vente->quantite_affichee);
        $this->assertEquals(3000, $vente->prix_affiche);
    }

    public function test_vente_refusee_si_unite_non_proposee_ou_stock_insuffisant_ou_a_perte(): void
    {
        $this->creerProduit(['unites_vente' => ['pcs', 'carton'], 'pcs_par_carton' => 24]);
        $p = Product::firstOrFail();

        $this->vendre($p, 1, 'paquet', 3000)->assertSessionHas('error');
        $this->vendre($p, 21, 'carton', 3000)->assertSessionHas('error'); // 504 pièces > 500
        $this->vendre($p, 1, 'carton', 2000)->assertSessionHas('error');  // < 24 × 100
        $this->assertSame(0, Sale::count());
        $this->assertSame(500, (int) StoreProduct::where('product_id', $p->id)->value('quantity'));
    }

    public function test_vente_sans_unite_reste_a_la_piece(): void
    {
        $this->creerProduit(['unites_vente' => ['pcs']]);
        $p = Product::firstOrFail();

        $this->actingAs($this->admin)->post(route('sales.store'), [
            'numeroFacture' => 'F-1', 'store_id' => $this->magasin->id, 'customer_id' => $this->client->id, 'avance' => 0,
            'sales' => [1 => ['product_id' => $p->id, 'quantity' => 3, 'prix' => 130, 'store_id' => $this->magasin->id]],
        ])->assertSessionHas('success');

        $this->assertSame(497, (int) StoreProduct::where('product_id', $p->id)->value('quantity'));
        $this->assertSame('3 pcs', Sale::firstOrFail()->quantite_affichee);
    }
}
