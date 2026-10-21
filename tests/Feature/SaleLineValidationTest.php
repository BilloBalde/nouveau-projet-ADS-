<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Facture;
use App\Models\Place;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Les lignes envoyées au point de vente n'étaient pas validées : seuls le numéro
 * de facture, la boutique, le client et l'avance l'étaient. Une quantité nulle
 * créait une facture à 0 GNF, et une quantité négative retranchait du montant
 * total sans contrepartie dès qu'une autre ligne gardait le total positif.
 */
class SaleLineValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $vendeur;

    private Store $boutique;

    private Product $produit;

    private Customer $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);

        $this->vendeur = User::create([
            'name' => 'Vendeur Test',
            'email' => 'vendeur.lignes@example.com',
            'username' => 'vendeur_lignes',
            'password' => Hash::make('password'),
            'role_id' => User::ROLE_ADMIN,
            'phone' => '620000010',
            'status' => 'Active',
            'token' => '',
        ]);

        $lieu = Place::create([
            'placeName' => 'Conakry',
            'countryName' => 'Guinée',
            'description' => 'Lieu de test',
        ]);

        $this->boutique = Store::create([
            'store_name' => 'Boutique Test',
            'place_id' => $lieu->id,
            'user_id' => $this->vendeur->id,
            'store_picture' => 'default.png',
            'description' => 'Boutique de test',
        ]);

        $this->produit = Product::create([
            'libelle' => 'Ciment 50kg',
            'sku' => 'CIM-50',
            'description' => 'Produit de test',
            'price_sale' => 5000,
            'price' => 6500,
        ]);

        $this->client = Customer::create([
            'customerName' => 'Client Test',
            'mark' => 'CT',
            'tel' => '620000011',
            'address' => 'Madina',
        ]);

        DB::table('store_products')->insert([
            'store_id' => $this->boutique->id,
            'product_id' => $this->produit->id,
            'quantity' => 100,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lignes
     */
    private function vendre(array $lignes)
    {
        return $this->actingAs($this->vendeur)->post(route('sales.store'), [
            'numeroFacture' => 'F-'.uniqid(),
            'store_id' => $this->boutique->id,
            'customer_id' => $this->client->id,
            'avance' => 0,
            'paid_by' => 'cash',
            'sales' => $lignes,
        ]);
    }

    private function ligne(int $quantite, float $prix = 10000): array
    {
        return [
            'product_id' => $this->produit->id,
            'quantity' => $quantite,
            'prix' => $prix,
        ];
    }

    public function test_une_vente_normale_est_acceptee(): void
    {
        $this->vendre([$this->ligne(3)])->assertSessionHasNoErrors();

        $this->assertSame(1, Facture::count());
        $this->assertSame(30000.0, (float) Facture::first()->montant_total);
    }

    public function test_une_quantite_nulle_est_refusee(): void
    {
        $this->vendre([$this->ligne(0)])->assertSessionHasErrors('sales.0.quantity');

        $this->assertSame(0, Facture::count());
    }

    public function test_une_quantite_negative_est_refusee(): void
    {
        $this->vendre([$this->ligne(-5)])->assertSessionHasErrors('sales.0.quantity');

        $this->assertSame(0, Facture::count());
    }

    /**
     * Le cas que le contrôle « avance > total » laissait passer : une ligne
     * négative masquée derrière une ligne positive plus grosse.
     */
    public function test_une_ligne_negative_masquee_par_une_ligne_positive_est_refusee(): void
    {
        $this->vendre([
            $this->ligne(50, 10000),
            $this->ligne(-4, 1000),
        ])->assertSessionHasErrors('sales.1.quantity');

        $this->assertSame(0, Facture::count());
        $this->assertSame(100, (int) DB::table('store_products')
            ->where('product_id', $this->produit->id)->value('quantity'));
    }

    public function test_une_quantite_decimale_est_refusee(): void
    {
        $this->vendre([[
            'product_id' => $this->produit->id,
            'quantity' => 2.5,
            'prix' => 10000,
        ]])->assertSessionHasErrors('sales.0.quantity');
    }

    public function test_un_prix_negatif_est_refuse(): void
    {
        $this->vendre([$this->ligne(2, -1000)])->assertSessionHasErrors('sales.0.prix');
    }

    public function test_un_produit_inexistant_est_refuse(): void
    {
        $this->vendre([[
            'product_id' => 999999,
            'quantity' => 1,
            'prix' => 10000,
        ]])->assertSessionHasErrors('sales.0.product_id');
    }

    public function test_une_vente_sans_aucune_ligne_est_refusee(): void
    {
        $this->vendre([])->assertSessionHasErrors('sales');

        $this->assertSame(0, Facture::count());
    }
}
