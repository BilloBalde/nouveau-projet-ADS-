<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RefreshPurchaseOrderStatuses extends Command
{
    protected $signature = 'purchase-orders:refresh-status';

    protected $description = 'Repasse à "reçu" les achats en attente dont la date de réception est arrivée';

    public function handle(): int
    {
        $count = PurchaseOrder::where('status', 'pending')
            ->whereNotNull('date_recu')
            ->whereDate('date_recu', '<=', Carbon::today())
            ->update(['status' => 'received']);

        $this->info("{$count} achat(s) passé(s) à \"Reçu\".");

        return self::SUCCESS;
    }
}
