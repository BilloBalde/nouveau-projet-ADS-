<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les transferts étaient exécutés immédiatement, sans demandeur ni
 * autorisation. Ils suivent maintenant un circuit :
 *
 *   pending → approved → in_transit → completed
 *                ↘ rejected / cancelled
 *
 * Les transferts existants ont déjà déplacé le stock : ils passent en
 * « completed », ce qui décrit exactement ce qui s'est produit. Leur
 * demandeur, lui, n'a jamais été enregistré et reste vide — on ne l'invente pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('id');
            $table->string('status', 20)->default('completed')->after('quantity');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('shipped_by')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();

            $table->index('status', 'stock_transfers_status_index');
            $table->index('reference', 'stock_transfers_reference_index');
        });

        // Anciens transferts : exécutés à leur création.
        DB::table('stock_transfers')->whereNull('reference')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('stock_transfers')->where('id', $row->id)->update([
                    'reference'   => 'TR-' . str_pad((string) $row->id, 6, '0', STR_PAD_LEFT),
                    'shipped_at'  => $row->created_at,
                    'received_at' => $row->created_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropIndex('stock_transfers_status_index');
            $table->dropIndex('stock_transfers_reference_index');
            $table->dropColumn([
                'reference', 'status', 'requested_by', 'approved_by', 'shipped_by', 'received_by',
                'approved_at', 'shipped_at', 'received_at', 'notes',
            ]);
        });
    }
};
