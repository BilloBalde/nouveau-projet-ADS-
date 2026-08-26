<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Place;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Approuver une commande crée la fiche client si elle n'existe pas encore.
 * La recherche se faisait sans `withTrashed()` : une fiche archivée restait
 * invisible, l'insertion se heurtait alors à l'index unique porté par `mark`
 * — que MySQL applique sans regarder `deleted_at` — et l'approbation échouait
 * sur une erreur SQL brute. Le client archivé ne pouvait plus jamais repasser
 * commande.
 */
class OrderApprovalTrashedCustomerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private Store $boutique;
    private Product $produit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesSeeder::class);

        $this->admin = User::create([
            'name' => 'Admin Commandes',
            'email' => 'admin.commandes@example.com',
            'username' => 'admin_commandes',
            'password' => Hash::make('password'),
            'role_id' => User::ROLE_ADMIN,
            'phone' => '620000020',
            'status' => 'Active',
            'token' => '',
        ]);

        $this->client = User::create([
            'name' => 'lala',
            'email' => 'c_620466915_test@clients.local',
            'username' => 'c_620466915',
            'password' => Hash::make('password'),
            'role_id' => User::ROLE_CUSTOMER,
            'phone' => '620466915',
            'status' => 'Active',
            'token' => '',
        ]);

        $lieu = Place::create([
            'placeName' => 'Conakry',
            'countryName' => 'Guinée',
            'description' => 'Lieu de test',
        ]);

        $this->boutique = Store::create([
            'store_name' => 'Quincaillerie Test',
            'place_id' => $lieu->id,
            'user_id' => $this->admin->id,
            'store_picture' => 'default.png',
            'description' => 'Boutique de test',
        ]);

        $this->produit = Product::create([
            'libelle' => 'Peinture blanche 4L',
            'sku' => 'PEI-4L',
            'description' => 'Produit de test',
            'price_sale' => 100000,
            'price' => 169000,
        ]);

        DB::table('store_products')->insert([
            'store_id' => $this->boutique->id,
            'product_id' => $this->produit->id,
            'quantity' => 50,
        ]);
    }

    /**
     * Crée une commande en attente de 5 unités, comme celle de la boutique.
     */
    private function commande(): Order
    {
        $adresse = DeliveryAddress::create([
            'user_id' => $this->client->id,
            'city' => 'Conakry',
            'phone' => $this->client->phone,
            'full_address' => 'Madina, Conakry',
        ]);

        $commande = Order::create([
            'user_id' => $this->client->id,
            'delivery_address_id' => $adresse->id,
            'total_amount' => 845000,
            'status' => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $commande->id,
            'product_id' => $this->produit->id,
            'quantity' => 5,
            'price' => 169000,
        ]);

        return $commande;
    }

    private function approuver(Order $commande)
    {
        return $this->actingAs($this->admin)->post(
            route('admin.orders.approve', $commande),
            ['store_id' => $this->boutique->id]
        );
    }

    /**
     * Le mark est déterministe : trois premières lettres du nom, puis l'id.
     */
    private function mark(): string
    {
        return strtoupper(substr($this->client->name, 0, 3)) . $this->client->id;
    }

    public function test_une_commande_dun_client_archive_est_approuvee(): void
    {
        // Une fiche client existe déjà, puis passe en corbeille.
        $fiche = Customer::create([
            'mark' => $this->mark(),
            'customerName' => $this->client->name,
            'tel' => $this->client->phone,
            'address' => 'Madina, Conakry',
            'email' => $this->client->email,
        ]);
        $fiche->delete();

        $this->assertSoftDeleted('customers', ['id' => $fiche->id]);

        $commande = $this->commande();
        $this->approuver($commande)->assertSessionMissing('error');

        $commande->refresh();
        $this->assertSame('approved', $commande->status);

        // La fiche est ressortie de la corbeille, sans doublon.
        $this->assertNotSoftDeleted('customers', ['id' => $fiche->id]);
        $this->assertSame(1, Customer::withTrashed()->where('mark', $this->mark())->count());
    }

    public function test_le_client_archive_conserve_son_historique(): void
    {
        $fiche = Customer::create([
            'mark' => $this->mark(),
            'customerName' => $this->client->name,
            'tel' => $this->client->phone,
            'address' => 'Madina, Conakry',
            'email' => $this->client->email,
        ]);
        $idOrigine = $fiche->id;
        $fiche->delete();

        $this->approuver($this->commande());

        // Même fiche, donc mêmes factures rattachées : une nouvelle fiche aurait
        // coupé le client de son historique.
        $this->assertSame($idOrigine, Customer::where('mark', $this->mark())->value('id'));
    }

    public function test_une_commande_dun_nouveau_client_cree_la_fiche(): void
    {
        $this->assertDatabaseCount('customers', 0);

        $commande = $this->commande();
        $this->approuver($commande)->assertSessionMissing('error');

        $commande->refresh();
        $this->assertSame('approved', $commande->status);
        $this->assertDatabaseHas('customers', ['mark' => $this->mark()]);
    }
}
