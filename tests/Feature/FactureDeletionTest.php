<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Facture;
use App\Models\Payment;
use App\Models\Place;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Une facture qui a encaissé de l'argent ne doit pas pouvoir être supprimée :
 * FactureController::destroy() efface définitivement les ventes et les paiements
 * (aucune de ces deux tables n'a de suppression douce).
 */
class FactureDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $utilisateur;

    private Store $boutique;

    private Product $produit;

    private Customer $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);

        $this->utilisateur = User::create([
            'name' => 'Vendeur Test',
            'email' => 'vendeur@example.com',
            'username' => 'vendeur_test',
            'password' => Hash::make('password'),
            'role_id' => User::ROLE_ADMIN,
            'phone' => '620000000',
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
            'user_id' => $this->utilisateur->id,
            'store_picture' => 'default.png',
            'description' => 'Boutique de test',
        ]);

        $this->produit = Product::create([
            'libelle' => 'Ciment 50kg',
            'sku' => 'CIM-50',
            'description' => 'Produit de test',
        ]);

        $this->client = Customer::create([
            'customerName' => 'Client Test',
            'mark' => 'CT',
            'tel' => '620000001',
            'address' => 'Madina',
        ]);

        DB::table('store_products')->insert([
            'store_id' => $this->boutique->id,
            'product_id' => $this->produit->id,
            'quantity' => 100,
        ]);
    }

    /**
     * Crée une facture avec une vente de 10 unités à 5 000, soit 50 000 au total.
     * $encaisse pilote le montant déjà reçu ; $statut permet de forcer une valeur
     * incohérente, comme il en existe en base réelle.
     */
    private function facture(float $encaisse, string $statut = 'non payé', float $avance = 0): Facture
    {
        $total = 50000.0;

        $facture = Facture::create([
            'numero_facture' => 'F-'.uniqid(),
            'customer_id' => $this->client->id,
            'store_id' => $this->boutique->id,
            'quantity' => 10,
            'montant_total' => $total,
            'avance' => $avance,
            'reste' => $total - $encaisse,
            'statut' => $statut,
        ]);

        Sale::create([
            'numeroFacture' => $facture->numero_facture,
            'product_id' => $this->produit->id,
            'store_id' => $this->boutique->id,
            'user_id' => $this->utilisateur->id,
            'quantity' => 10,
            'prix' => 5000,
            'prixTotal' => $total,
            'interet' => 0,
        ]);

        return $facture;
    }

    private function supprimer(Facture $facture)
    {
        return $this->actingAs($this->utilisateur)
            ->delete(route('factures.destroy', $facture));
    }

    public function test_une_facture_sans_encaissement_est_supprimable(): void
    {
        $facture = $this->facture(encaisse: 0);

        $this->supprimer($facture)->assertRedirect(route('factures.index'));

        $this->assertSoftDeleted('factures', ['id' => $facture->id]);
        $this->assertDatabaseMissing('sales', ['numeroFacture' => $facture->numero_facture]);

        // Le stock est bien restitué : 100 - 0 + 10 vendus rendus.
        $this->assertSame(110, (int) DB::table('store_products')
            ->where('store_id', $this->boutique->id)
            ->where('product_id', $this->produit->id)
            ->value('quantity'));
    }

    public function test_une_facture_soldee_ne_peut_pas_etre_supprimee(): void
    {
        $facture = $this->facture(encaisse: 50000, statut: 'payé');

        $this->supprimer($facture)->assertSessionHas('error');

        $this->assertNotSoftDeleted('factures', ['id' => $facture->id]);
    }

    public function test_une_facture_partiellement_payee_ne_peut_pas_etre_supprimee(): void
    {
        $facture = $this->facture(encaisse: 20000, statut: 'partiel');

        $this->supprimer($facture)->assertSessionHas('error');

        $this->assertNotSoftDeleted('factures', ['id' => $facture->id]);
    }

    /**
     * Le cas que le statut laissait passer : le code n'écrit jamais « partiel »,
     * une facture à moitié payée reste donc marquée « non payé ». En base réelle,
     * six factures encaissées portent ce statut.
     */
    public function test_une_facture_partiellement_payee_mais_marquee_non_payee_est_refusee(): void
    {
        $facture = $this->facture(encaisse: 20000, statut: 'non payé');

        $this->assertSame('non payé', $facture->statut);

        $this->supprimer($facture)->assertSessionHas('error');

        $this->assertNotSoftDeleted('factures', ['id' => $facture->id]);
    }

    /**
     * Même angle mort du côté de `avance` : il n'est renseigné qu'à la création de
     * la vente et jamais mis à jour ensuite.
     */
    public function test_une_facture_avec_seulement_une_avance_est_refusee(): void
    {
        $facture = $this->facture(encaisse: 0, statut: 'non payé', avance: 15000);

        $this->supprimer($facture)->assertSessionHas('error');

        $this->assertNotSoftDeleted('factures', ['id' => $facture->id]);
    }

    public function test_un_refus_ne_laisse_aucune_trace(): void
    {
        $facture = $this->facture(encaisse: 20000);

        Payment::create([
            'facture_id' => $facture->id,
            'versement' => 20000,
            'total_paye' => 20000,
            'reste' => 30000,
            'receipt_number' => 'RCF-TEST-1',
            'note' => 'Paiement de test',
        ]);

        $this->supprimer($facture);

        $this->assertDatabaseHas('payments', ['facture_id' => $facture->id, 'versement' => 20000]);
        $this->assertDatabaseHas('sales', ['numeroFacture' => $facture->numero_facture]);
        $this->assertNotSoftDeleted('factures', ['id' => $facture->id]);

        // Le stock ne doit pas avoir bougé : la restitution n'a pas eu lieu.
        $this->assertSame(100, (int) DB::table('store_products')
            ->where('store_id', $this->boutique->id)
            ->where('product_id', $this->produit->id)
            ->value('quantity'));
    }

    public function test_un_paiement_enregistre_suffit_a_bloquer(): void
    {
        // reste == montant_total et avance nulle : seul le paiement trahit
        // l'encaissement.
        $facture = $this->facture(encaisse: 0);

        Payment::create([
            'facture_id' => $facture->id,
            'versement' => 5000,
            'total_paye' => 5000,
            'reste' => 45000,
            'receipt_number' => 'RCF-TEST-2',
            'note' => 'Paiement de test',
        ]);

        $this->supprimer($facture)->assertSessionHas('error');

        $this->assertNotSoftDeleted('factures', ['id' => $facture->id]);
    }

    /**
     * Le POS crée une ligne de paiement même pour une avance nulle : sa seule
     * présence ne doit donc pas bloquer la suppression.
     */
    public function test_une_ligne_de_paiement_a_zero_ne_bloque_pas(): void
    {
        $facture = $this->facture(encaisse: 0);

        Payment::create([
            'facture_id' => $facture->id,
            'versement' => 0,
            'total_paye' => 0,
            'reste' => 50000,
            'receipt_number' => 'RCF-TEST-3',
            'note' => 'Paiement de test',
        ]);

        $this->supprimer($facture)->assertRedirect(route('factures.index'));

        $this->assertSoftDeleted('factures', ['id' => $facture->id]);
    }
}
