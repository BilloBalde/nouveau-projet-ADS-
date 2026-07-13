<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

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
        DB::table('users')->insert([
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'phone' => '19511319802',
                'email' => 'info@ibratechengineer.com',
                'role_id' => 1,
                'status' => 'Active',
                'token' => hash('sha256', time()),
                'password' => Hash::make('EdaagPOS@'),
                'motdepasse' => 'EdaagPOS@',
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
                'password' => Hash::make('Polimax2026@'),
                'motdepasse' => 'Polimax2026@',
                'description' => 'Le manager général de la plateforme. Il a la main mise sur toutes les entités. Il supervise les faits et gestes des employés',
            ],
        ]);
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
