<?php

use App\Support\LocationPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permissions stock / transferts / emplacements et rôles Magasinier et
 * Responsable magasin. Voir App\Support\LocationPermissions.
 *
 * `stores.all` distingue les profils à vue globale (admin, manager, comptable,
 * achats) des profils rattachés à un magasin (vendeur, magasinier,
 * responsable) : la restriction par magasin se lit dans les permissions, pas
 * dans un numéro de rôle codé en dur.
 */
return new class extends Migration
{
    public function up(): void
    {
        LocationPermissions::install();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('slug', LocationPermissions::slugs())->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('user_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
