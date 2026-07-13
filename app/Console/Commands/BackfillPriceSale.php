<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Console\Command;

class BackfillPriceSale extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-price-sale';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remplit products.price_sale (vide/0 sur les données importées) avec le prix du dernier achat réel enregistré, pour chaque produit qui en a un.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $updated = 0;
        $skipped = 0;

        Product::chunk(50, function ($products) use (&$updated, &$skipped) {
            foreach ($products as $product) {
                $latestPrice = Purchase::where('product_id', $product->id)->latest()->value('price');

                if ($latestPrice === null) {
                    $skipped++;
                    continue;
                }

                $product->price_sale = $latestPrice;
                $product->save();
                $updated++;
            }
        });

        $this->info("price_sale mis à jour pour {$updated} produit(s), {$skipped} laissé(s) inchangé(s) (aucun achat historique).");
    }
}
