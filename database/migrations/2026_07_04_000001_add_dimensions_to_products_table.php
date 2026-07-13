<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'taille')) {
                $table->string('taille', 50)->nullable()->after('description');
            }
            if (!Schema::hasColumn('products', 'hauteur')) {
                $table->decimal('hauteur', 10, 2)->nullable()->after('taille');
            }
            if (!Schema::hasColumn('products', 'largeur')) {
                $table->decimal('largeur', 10, 2)->nullable()->after('hauteur');
            }
            if (!Schema::hasColumn('products', 'epaisseur')) {
                $table->decimal('epaisseur', 10, 2)->nullable()->after('largeur');
            }
            if (!Schema::hasColumn('products', 'poids')) {
                $table->decimal('poids', 10, 2)->nullable()->after('epaisseur');
            }
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['taille', 'hauteur', 'largeur', 'epaisseur', 'poids'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
