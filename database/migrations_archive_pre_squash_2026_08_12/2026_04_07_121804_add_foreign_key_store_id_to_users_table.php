<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Ajouter la colonne store_id si elle n'existe pas
        if (!Schema::hasColumn('users', 'store_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('store_id')->nullable()->after('role_id');
            });
        }

        // 2. Ajouter la clé étrangère (si la colonne existe et que la table stores existe)
        if (Schema::hasColumn('users', 'store_id') && Schema::hasTable('stores')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'store_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['store_id']);
                $table->dropColumn('store_id');
            });
        }
    }
};