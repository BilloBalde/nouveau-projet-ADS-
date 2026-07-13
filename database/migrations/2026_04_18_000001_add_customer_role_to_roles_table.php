<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rôle dédié aux clients e-commerce (séparé admin / manager / gérant de boutique).
     */
    public function up(): void
    {
        $exists = DB::table('roles')->where('slug', 'customer')->exists();
        if (! $exists) {
            DB::table('roles')->insert([
                'slug' => 'customer',
                'nameRole' => 'Client',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->where('slug', 'customer')->delete();
    }
};
