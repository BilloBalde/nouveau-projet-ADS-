<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('roles')->insert([
            [
                'slug' => 'admin',
                'nameRole' => 'Administrator'
            ],
            [
                'slug' => 'superuser',
                'nameRole' => 'Store Administrator'
            ],
            [
                'slug' => 'user',
                'nameRole' => 'store manager'
            ]
        ]);
        // Les mots de passe ne sont plus codés en dur dans ce fichier (il est versionné
        // dans Git). Fournir SMH_SEED_ADMIN_PASSWORD / SMH_SEED_MANAGER_PASSWORD dans
        // .env avant de migrer ; sinon un mot de passe aléatoire est généré et affiché
        // une seule fois dans la sortie de la commande.
        $adminPassword   = env('SMH_SEED_ADMIN_PASSWORD') ?: Str::random(20);
        $managerPassword = env('SMH_SEED_MANAGER_PASSWORD') ?: Str::random(20);

        DB::table('users')->insert([
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'phone' => '19511319802',
                'email' => 'info@ibratechengineer.com',
                'role_id' => 1,
                'status' => 'Active',
                'token' => hash('sha256', time()),
                'password' => Hash::make($adminPassword),
                'motdepasse' => $adminPassword,
                'description' => 'Le manager principale du site, il est chargé de la gestion complète et mis à jour du site',
            ],
            [
                'name' => 'GENERAL MANAGER',
                'username' => 'generalmanager',
                'phone' => '19511319802',
                'email' => 'thiernoamadousow219@gmail.com',
                'role_id' => 2,
                'status' => 'Active',
                'token' => hash('sha256', time()),
                'password' => Hash::make($managerPassword),
                'motdepasse' => $managerPassword,
                'description' => 'Le manager général de la plateforme. Il a la main mise sur toutes les entités. Il supervise les faits et gestes des employés',
            ],
        ]);

        if (! env('SMH_SEED_ADMIN_PASSWORD') || ! env('SMH_SEED_MANAGER_PASSWORD')) {
            echo "\n[seed] Mot de passe admin généré : {$adminPassword}\n";
            echo "[seed] Mot de passe manager généré : {$managerPassword}\n";
            echo "[seed] Notez-les maintenant, ils ne seront plus jamais affichés.\n";
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
