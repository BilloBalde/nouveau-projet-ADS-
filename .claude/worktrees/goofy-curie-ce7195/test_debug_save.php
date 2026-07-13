<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\DB;

DB::listen(function ($query) {
    if (stripos($query->sql, 'products') !== false) {
        echo "SQL: " . $query->sql . " | BINDINGS: " . json_encode($query->bindings) . PHP_EOL;
    }
});

$product = Product::where('libelle', 'Vinyl Blanc')->first();
echo "Loaded product id={$product->id}\n";
$product->qtityCtn = 1;
$product->price = 27200;
$product->price_sale = 27700;
$product->price_carton = 29500;
$product->image = 'somefile.jpg';
echo "Dirty attributes: " . json_encode($product->getDirty()) . PHP_EOL;
$product->save();
echo "After save, fresh from DB: " . json_encode($product->fresh()->only(['qtityCtn','price','price_sale','price_carton'])) . PHP_EOL;
