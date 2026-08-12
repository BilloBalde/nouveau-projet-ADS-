<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module dettes (saisie manuelle de dettes) jamais réellement utilisé — 1 seul
     * enregistrement en production, le vrai suivi des dettes clients se fait via les
     * factures impayées (cf. SendDebtReminders, qui ne dépend plus que de `factures`).
     */
    public function up(): void
    {
        if (Schema::hasColumn('debt_reminders', 'dette_id')) {
            Schema::table('debt_reminders', function (Blueprint $table) {
                $table->dropForeign(['dette_id']);
                $table->dropColumn('dette_id');
            });
        }

        Schema::dropIfExists('payment_dettes');
        Schema::dropIfExists('dettes');
    }

    public function down(): void
    {
        Schema::create('dettes', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->bigInteger('customer_id')->unsigned();
            $table->foreign('customer_id')->references('id')->on('customers')->onUpdate('cascade')->onDelete('restrict');
            $table->bigInteger('store_id')->unsigned();
            $table->foreign('store_id')->references('id')->on('stores')->onUpdate('cascade')->onDelete('restrict');
            $table->date('endettement_date');
            $table->double('montant_total', 10, 2);
            $table->double('montant_paid', 10, 2);
            $table->double('reste', 10, 2);
            $table->string('preuve')->default('default.png');
            $table->enum('status', ['pending', 'paid', 'cancel'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_dettes', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->bigInteger('dette_id')->unsigned();
            $table->foreign('dette_id')->references('id')->on('dettes')->onUpdate('cascade')->onDelete('restrict');
            $table->decimal('versement', 15, 2);
            $table->enum('paid_by', ['cash', 'check', 'orange money'])->default('cash');
            $table->string('notes');
            $table->timestamps();
        });

        if (! Schema::hasColumn('debt_reminders', 'dette_id')) {
            Schema::table('debt_reminders', function (Blueprint $table) {
                $table->foreignId('dette_id')->nullable()->after('customer_id')->constrained('dettes')->cascadeOnDelete();
            });
        }
    }
};
