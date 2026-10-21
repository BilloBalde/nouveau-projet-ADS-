<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Facture;
use App\Models\Place;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * total_taken, total_repaid et balance étaient tenus par incréments, à chaque
 * vente et à chaque encaissement. Un incrément manqué s'installait
 * définitivement : l'approbation d'une commande en ligne créait une facture
 * sans jamais toucher aux compteurs, et le client restait débiteur d'un montant
 * qui n'apparaissait nulle part.
 *
 * Ils sont désormais recalculés depuis les factures à chaque mutation, ce qui
 * rend toute dérive auto-réparable.
 */
class CustomerCountersTest extends TestCase
{
    use RefreshDatabase;

    private Customer $client;

    private Store $boutique;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);

        $gerant = User::create([
            'name' => 'Gerant Compteurs',
            'email' => 'gerant.compteurs@example.com',
            'username' => 'gerant_compteurs',
            'password' => Hash::make('password'),
            'role_id' => User::ROLE_ADMIN,
            'phone' => '620000030',
            'status' => 'Active',
            'token' => '',
        ]);

        $lieu = Place::create([
            'placeName' => 'Conakry',
            'countryName' => 'Guinée',
            'description' => 'Lieu de test',
        ]);

        $this->boutique = Store::create([
            'store_name' => 'Boutique Compteurs',
            'place_id' => $lieu->id,
            'user_id' => $gerant->id,
            'store_picture' => 'default.png',
            'description' => 'Boutique de test',
        ]);

        $this->client = Customer::create([
            'customerName' => 'Client Compteurs',
            'mark' => 'CPT',
            'tel' => '620000031',
            'address' => 'Madina',
            'total_taken' => 0,
            'total_repaid' => 0,
            'balance' => 0,
        ]);
    }

    private function facture(float $total, float $encaisse): Facture
    {
        return Facture::create([
            'numero_facture' => 'F-'.uniqid(),
            'customer_id' => $this->client->id,
            'store_id' => $this->boutique->id,
            'quantity' => 1,
            'montant_total' => $total,
            'avance' => $encaisse,
            'reste' => $total - $encaisse,
            'statut' => $total - $encaisse <= 0 ? 'payé' : 'non payé',
        ]);
    }

    public function test_les_compteurs_se_deduisent_des_factures(): void
    {
        $this->facture(100000, 40000);
        $this->facture(50000, 0);

        $this->client->recalculerCompteurs();
        $this->client->refresh();

        $this->assertSame(150000.0, (float) $this->client->total_taken);
        $this->assertSame(40000.0, (float) $this->client->total_repaid);
        // Convention : positif = le client doit.
        $this->assertSame(110000.0, (float) $this->client->balance);
    }

    public function test_une_derive_est_reparee_a_la_mutation_suivante(): void
    {
        $this->facture(100000, 0);

        // On simule un compteur parti à la dérive, comme le produisait un
        // incrément manqué.
        $this->client->forceFill(['balance' => 999999, 'total_repaid' => -50000])->save();

        $this->client->recalculerCompteurs();
        $this->client->refresh();

        $this->assertSame(100000.0, (float) $this->client->balance);
        $this->assertSame(0.0, (float) $this->client->total_repaid);
    }

    public function test_un_client_solde_a_un_solde_nul(): void
    {
        $this->facture(80000, 80000);

        $this->client->recalculerCompteurs();
        $this->client->refresh();

        $this->assertSame(0.0, (float) $this->client->balance);
        $this->assertSame(80000.0, (float) $this->client->total_repaid);
    }

    /**
     * Le solde doit toujours valoir la somme des restes — c'est ce que lit le
     * tableau de bord pour son total de crédits, et l'écran des créanciers.
     */
    public function test_le_solde_egale_la_somme_des_restes(): void
    {
        $this->facture(200000, 150000);
        $this->facture(70000, 70000);
        $this->facture(30000, 0);

        $this->client->recalculerCompteurs();
        $this->client->refresh();

        $restes = Facture::where('customer_id', $this->client->id)->sum('reste');

        $this->assertSame((float) $restes, (float) $this->client->balance);
        $this->assertSame(
            (float) $this->client->total_taken - (float) $this->client->total_repaid,
            (float) $this->client->balance
        );
    }

    public function test_une_facture_archivee_ne_compte_plus(): void
    {
        $active = $this->facture(100000, 0);
        $archivee = $this->facture(500000, 0);
        $archivee->delete();

        $this->client->recalculerCompteurs();
        $this->client->refresh();

        // Seule la facture active pèse sur le solde.
        $this->assertSame(100000.0, (float) $this->client->balance);
        $this->assertSame(100000.0, (float) $this->client->total_taken);
    }
}
