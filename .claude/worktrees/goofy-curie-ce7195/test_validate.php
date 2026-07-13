<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

$updateRequest = Request::create("/proformas/updating/999", 'POST', [
    'date_achat' => '2026-07-12',
    'shippment' => 6000,
    'products' => [
        [
            'productName' => 'Vinyl Blanc',
            'cartons' => 15,
            'unit_price_purchase' => 27200,
            'unit_price_sale' => 27700,
            'ctn_price_sale' => 29500,
            'subtotal' => 408000,
        ],
    ],
]);
app()->instance('request', $updateRequest);

$validatedData = $updateRequest->validate([
    'products.*.productName' => 'required|string|max:255',
    'products.*.cartons' => 'required|integer|min:1',
    'products.*.unit_price_purchase' => 'required|numeric|min:0',
    'products.*.unit_price_sale' => 'required|numeric|min:0',
    'products.*.ctn_price_sale' => 'required|numeric|min:0',
    'products.*.subtotal' => 'required|numeric|min:0',
    'products.*.image' => 'image',
]);

var_dump($validatedData);
