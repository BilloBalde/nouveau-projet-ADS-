<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Achat;
use App\Models\LigneCommande;
use App\Models\StoreProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

DB::listen(function ($query) {
    if (stripos($query->sql, 'products') !== false && stripos($query->sql, 'update') !== false) {
        echo ">>> SQL: " . $query->sql . " | BINDINGS: " . json_encode($query->bindings) . PHP_EOL;
    }
});

$product = Product::where('libelle', 'Vinyl Blanc')->first();
echo "Using product #{$product->id} ({$product->libelle}), qtityCtn before = " . var_export($product->qtityCtn, true) . ", price_carton before = " . var_export($product->price_carton, true) . PHP_EOL;

$user = User::first();
Auth::login($user);

$controller = $app->make(App\Http\Controllers\LigneCommandeController::class);

// ---- 1) CREATE ACHAT (store) ----
$storeRequest = Request::create('/proformas', 'POST', [
    'date_achat' => '2026-07-12',
    'shippment' => 5000,
    'store_id' => 3,
    'products' => [
        [
            'productName' => $product->libelle,
            'category_id' => 1,
            'cartons' => 10,
            'unit_price_purchase' => 27000,
            'unit_price_sale' => 27500,
            'price_sale' => 29000,
            'subtotal' => 275000,
        ],
    ],
    'total_subtotal' => 275000,
]);
$storeRequest->headers->set('Accept', 'application/json');
app()->instance('request', $storeRequest);
$response = $controller->store($storeRequest);
echo "store() response: " . $response->getContent() . PHP_EOL;

$achat = Achat::latest('id')->first();
echo "Created achat #{$achat->id}, total_ctns={$achat->total_ctns}, total_pcs={$achat->total_pcs}, total_amount={$achat->total_amount}" . PHP_EOL;

$ligne = LigneCommande::where('achat_id', $achat->id)->first();
echo "LigneCommande: cartons={$ligne->cartons}, quantity={$ligne->quantity}, unit_price_purchase={$ligne->unit_price_purchase}, total_price_purchase={$ligne->total_price_purchase}, montant_sale={$ligne->montant_sale}" . PHP_EOL;

$product->refresh();
echo "Product after store(): qtityCtn={$product->qtityCtn}, price_carton=" . var_export($product->price_carton, true) . ", price={$product->price}" . PHP_EOL;

$sp = StoreProduct::where('store_id', 3)->where('product_id', $product->id)->first();
echo "StoreProduct: quantity={$sp->quantity}, ctns={$sp->ctns}" . PHP_EOL;

// Manually set qtityCtn to a nonzero real-world value (as the Facture flow would) to verify the achat edit no longer clobbers it
$product->qtityCtn = 24;
$product->save();
echo "Manually set qtityCtn=24 on product (simulating Facture flow) before editing achat." . PHP_EOL;

// ---- 2) EDIT ACHAT (updateInvoice) ----
$updateRequest = Request::create("/proformas/updating/{$achat->id}", 'POST', [
    'date_achat' => '2026-07-12',
    'shippment' => 6000,
    'products' => [
        [
            'productName' => $product->libelle,
            'cartons' => 15,
            'unit_price_purchase' => 27200,
            'unit_price_sale' => 27700,
            'ctn_price_sale' => 29500,
            'subtotal' => 408000,
        ],
    ],
]);
$updateRequest->headers->set('Accept', 'application/json');
app()->instance('request', $updateRequest);
$response = $controller->updateInvoice($updateRequest, $achat->id);
echo "updateInvoice() response: " . $response->getContent() . PHP_EOL;

$achat->refresh();
echo "Achat after edit: total_ctns={$achat->total_ctns}, total_pcs={$achat->total_pcs}, total_amount={$achat->total_amount}" . PHP_EOL;

$ligne->refresh();
echo "LigneCommande after edit: cartons={$ligne->cartons}, quantity={$ligne->quantity}, unit_price_purchase={$ligne->unit_price_purchase}, total_price_purchase={$ligne->total_price_purchase}, montant_sale={$ligne->montant_sale}, ctn_price_sale={$ligne->ctn_price_sale}" . PHP_EOL;

$product->refresh();
echo "Product after updateInvoice(): qtityCtn={$product->qtityCtn} (expected still 24, NOT clobbered to 1), price_carton={$product->price_carton}, price={$product->price}, price_sale={$product->price_sale}" . PHP_EOL;

$sp->refresh();
echo "StoreProduct after edit (NOTE: updateInvoice doesn't touch this store's stock for this store_id since proforma->store_id wasn't in update payload): quantity={$sp->quantity}, ctns={$sp->ctns}" . PHP_EOL;

echo "DONE\n";
