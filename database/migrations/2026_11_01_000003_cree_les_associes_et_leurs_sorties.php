<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Associés : une part du bénéfice de l'année, et des sorties d'argent.
 *
 * - associates                : les associés de la boutique ;
 * - associate_profit_shares   : la part du bénéfice attribuée à chacun pour
 *                               une année, saisie à la main. Ce qui n'est
 *                               attribué à personne reste à la boutique ;
 * - associate_year_closings   : la clôture de l'année, figée : report de
 *                               l'année précédente + part − sorties = solde.
 *                               Un solde positif est versé à l'associé, un
 *                               solde négatif est reporté sur l'année suivante.
 *
 * Une dépense est désormais une sortie donnée à un associé, avec un motif :
 * les catégories de dépense disparaissent. Le montant passe en 15 chiffres —
 * en decimal(8,2), une dépense d'un million de francs guinéens dépassait la
 * colonne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('associates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 40)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('associate_profit_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('associate_id')->constrained('associates')->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('amount', 15, 2);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['associate_id', 'year'], 'associate_profit_shares_unique');
        });

        Schema::create('associate_year_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('associate_id')->constrained('associates')->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('carried_in', 15, 2)->default(0);   // report de l'année précédente (négatif = dû à la boutique)
            $table->decimal('profit_share', 15, 2)->default(0);
            $table->decimal('withdrawals', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);      // report + part − sorties
            $table->decimal('paid', 15, 2)->default(0);         // versé à l'associé
            $table->decimal('carried_out', 15, 2)->default(0);  // reporté sur l'année suivante
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at');
            $table->timestamps();
            $table->unique(['associate_id', 'year'], 'associate_year_closings_unique');
            $table->index('year', 'associate_year_closings_year_index');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('associate_id')->nullable()->after('reference')->constrained('associates')->restrictOnDelete();
        });

        if (Schema::hasColumn('expenses', 'expense_categories_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropForeign(['expense_categories_id']);
            });
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn(['expense_categories_id', 'exp_mode', 'balance']);
            });
        }

        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->default(0)->change();
        });
        DB::table('expenses')->whereNull('amount')->update(['amount' => 0]);

        Schema::dropIfExists('expense_categories');
    }

    public function down(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('categoryName');
            $table->timestamps();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('associate_id');
            $table->foreignId('expense_categories_id')->nullable()->constrained('expense_categories')->cascadeOnDelete();
            $table->decimal('balance', 8, 2)->nullable();
            $table->enum('exp_mode', ['others', 'ukexpense'])->nullable();
        });

        Schema::dropIfExists('associate_year_closings');
        Schema::dropIfExists('associate_profit_shares');
        Schema::dropIfExists('associates');
    }
};
