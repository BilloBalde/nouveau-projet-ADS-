<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = [
                'taille', 'hauteur', 'largeur', 'epaisseur', 'poids', 'nbr_unite',
                'is_best', 'price_carton',
            ];
            $existing = array_filter($columns, fn ($c) => Schema::hasColumn('products', $c));
            if ($existing) {
                $table->dropColumn($existing);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'taille')) {
                $table->string('taille', 50)->nullable();
            }
            if (!Schema::hasColumn('products', 'hauteur')) {
                $table->decimal('hauteur', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'largeur')) {
                $table->decimal('largeur', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'epaisseur')) {
                $table->decimal('epaisseur', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'poids')) {
                $table->decimal('poids', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'nbr_unite')) {
                $table->unsignedInteger('nbr_unite')->nullable();
            }
            if (!Schema::hasColumn('products', 'is_best')) {
                $table->boolean('is_best')->default(false);
            }
            if (!Schema::hasColumn('products', 'price_carton')) {
                $table->double('price_carton', 10, 2)->nullable();
            }
        });
    }
};
