<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Les libellés affichés à la création d'utilisateur prêtaient à confusion :
     * "Store Administrator" (rôle 2, slug superuser — accès large, sans boutique)
     * sonnait comme un rôle lié à une boutique, et "store manager" (rôle 3, slug
     * user — nécessite une boutique assignée) sonnait comme le manager général.
     * C'est l'inverse de la réalité et a directement causé une boucle de
     * redirection infinie pour un utilisateur créé avec le mauvais rôle.
     */
    public function up(): void
    {
        DB::table('roles')->where('id', 2)->update(['nameRole' => 'Manager']);
        DB::table('roles')->where('id', 3)->update(['nameRole' => 'Vendeur']);
    }

    public function down(): void
    {
        DB::table('roles')->where('id', 2)->update(['nameRole' => 'Store Administrator']);
        DB::table('roles')->where('id', 3)->update(['nameRole' => 'store manager']);
    }
};
