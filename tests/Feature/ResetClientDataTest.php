<?php

namespace Tests\Feature;

use App\Console\Commands\ResetClientData;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * « php artisan client:reset-data » est la dernière manœuvre avant de remettre
 * le logiciel à un nouveau commerçant : elle efface les données de
 * démonstration et garde les comptes, les droits et les réglages.
 *
 * Sa liste de tables a été écrite une fois, puis le projet a continué. Le
 * module de comptage, les fonds d'associés, le journal des mouvements de stock
 * et les affectations de vendeurs sont arrivés après, sans y être ajoutés : la
 * base du nouveau client aurait gardé l'historique de l'ancien — et le premier
 * comptage aurait déduit un stock de départ de mouvements qui ne le concernent
 * pas.
 *
 * Le dernier test est celui qui compte : il relit la base entière et refuse
 * qu'une table échappe aux deux listes. Il attrapera la prochaine.
 */
class ResetClientDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_base_entiere_est_couverte_par_les_deux_listes(): void
    {
        $connues = array_merge(
            (new \ReflectionClass(ResetClientData::class))->getConstant('TABLES'),
            ResetClientData::CONSERVEES,
        );

        $tables = $this->tablesDeLaBase();

        // Un test qui ne lit aucune table passerait toujours.
        $this->assertGreaterThan(40, count($tables), 'La liste des tables de la base est anormalement courte.');
        $this->assertContains('products', $tables);
        $this->assertContains('inventory_sessions', $tables);

        $oubliees = array_values(array_diff($tables, $connues));

        $this->assertSame([], $oubliees,
            "Table ni vidée ni explicitement conservée — décidez laquelle des deux listes la reçoit :\n  "
            .implode("\n  ", $oubliees));
    }

    public function test_les_donnees_de_demonstration_disparaissent(): void
    {
        Artisan::call('db:seed', ['--force' => true]);

        $this->assertGreaterThan(0, DB::table('products')->count(), 'Le décor doit contenir des données.');

        Artisan::call('client:reset-data', ['--force' => true]);

        foreach (['products', 'stores', 'customers', 'store_products', 'stock_movements',
            'inventory_sessions', 'inventory_session_lines', 'funds', 'store_assignments'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), "La table $table n'a pas été vidée.");
        }
    }

    public function test_les_comptes_les_droits_et_la_fiche_entreprise_sont_conserves(): void
    {
        Artisan::call('db:seed', ['--force' => true]);

        $comptes = User::count();
        $roles = Role::count();
        $permissions = Permission::count();
        $entreprise = Company::current()->name;

        Artisan::call('client:reset-data', ['--force' => true]);

        $this->assertSame($comptes, User::count(), 'Des comptes ont été supprimés.');
        $this->assertSame($roles, Role::count());
        $this->assertSame($permissions, Permission::count());
        $this->assertSame($entreprise, Company::current()?->name, 'La fiche entreprise a disparu.');
    }

    /**
     * TRUNCATE ignore les règles ON DELETE : un compte resté rattaché à une
     * boutique effacée pointe dans le vide, et l'application le croit toujours
     * cloisonné à un magasin qui n'existe plus.
     */
    public function test_aucun_compte_ne_reste_rattache_a_une_boutique_effacee(): void
    {
        Artisan::call('db:seed', ['--force' => true]);

        Artisan::call('client:reset-data', ['--force' => true]);

        $this->assertSame(0, DB::table('users')->whereNotNull('store_id')->count());
    }

    /** Sans confirmation, la commande ne touche à rien. */
    public function test_sans_confirmation_rien_n_est_efface(): void
    {
        Artisan::call('db:seed', ['--force' => true]);
        $produits = DB::table('products')->count();

        $this->artisan('client:reset-data')
            ->expectsConfirmation('Confirmer la suppression ?', 'no')
            ->assertSuccessful();

        $this->assertSame($produits, DB::table('products')->count());
    }

    /** @return array<int, string> */
    private function tablesDeLaBase(): array
    {
        $tables = array_map(
            fn ($t) => is_array($t) ? reset($t) : (is_object($t) ? reset(((array) $t)) : $t),
            Schema::getTables()
        );

        // Schema::getTables() rend des tableaux détaillés selon la version :
        // on ne garde que le nom.
        $noms = array_map(fn ($t) => is_string($t) ? $t : (string) $t, array_column(Schema::getTables(), 'name') ?: $tables);

        sort($noms);

        return $noms;
    }
}
