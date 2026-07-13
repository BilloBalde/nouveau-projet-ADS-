<?php

namespace App\Console\Commands;

use App\Models\StockHistory;
use Illuminate\Console\Command;

class RecalculateStockHistories extends Command
{
   protected $signature = 'stock:recalculate {store_id?}';
    protected $description = 'Recalcule les disponibilités pour chaque magasin';

    public function handle()
    {
        $storeId = $this->argument('store_id');
        $query = StockHistory::orderBy('id');
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $histories = $query->get()->groupBy('store_id');

        foreach ($histories as $sid => $items) {
            $running = 0;
            foreach ($items as $history) {
                $history->dispo_before = $running;
                $history->dispo_after = $running + $history->amount;
                $history->saveQuietly(); // n’envoie pas d’événement
                $running = $history->dispo_after;
            }
            $this->info("Store $sid : dernière dispo = $running");
        }
    }
}
