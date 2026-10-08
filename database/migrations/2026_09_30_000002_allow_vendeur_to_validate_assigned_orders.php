<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Une commande en ligne attribuée à une boutique doit pouvoir y être traitée,
 * même quand la boutique n'a que des vendeurs. Le vendeur reçoit donc
 * orders.validate. Il reste cantonné à son périmètre : il ne voit que les
 * commandes attribuées à sa boutique (Order::visibleTo) et ne puise que dans
 * le stock de sa boutique (canAccessStore) ; les commandes restées au siège
 * ne lui sont jamais visibles.
 */
return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')->where('slug', 'user')->value('id');
        $permissionId = DB::table('permissions')->where('slug', 'orders.validate')->value('id');

        if (! $roleId || ! $permissionId) {
            return; // Base vierge : PermissionSeeder s'en charge.
        }

        $now = now();
        DB::table('role_permissions')->updateOrInsert(
            ['role_id' => $roleId, 'permission_id' => $permissionId],
            ['created_at' => $now, 'updated_at' => $now]
        );

        // Vendeurs aux permissions personnalisées : ils ne suivent plus le preset.
        $customised = DB::table('user_permissions')
            ->join('users', 'users.id', '=', 'user_permissions.user_id')
            ->where('users.role_id', $roleId)
            ->distinct()
            ->pluck('user_permissions.user_id');

        foreach ($customised as $userId) {
            DB::table('user_permissions')->updateOrInsert(
                ['user_id' => $userId, 'permission_id' => $permissionId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('slug', 'user')->value('id');
        $permissionId = DB::table('permissions')->where('slug', 'orders.validate')->value('id');

        if ($roleId && $permissionId) {
            DB::table('role_permissions')->where(['role_id' => $roleId, 'permission_id' => $permissionId])->delete();
        }
    }
};
