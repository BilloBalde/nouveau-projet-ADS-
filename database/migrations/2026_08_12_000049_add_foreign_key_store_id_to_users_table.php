<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `users`.`store_id` référence `stores`, mais `stores` référence
 * aussi `users` (cycle à deux) — la contrainte ne peut être ajoutée qu'une
 * fois les deux tables créées.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('store_id', 'users_store_id_foreign')
                ->references('id')->on('stores')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_store_id_foreign');
        });
    }
};
