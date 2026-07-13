<?php

namespace App\Http\Controllers;

use App\Models\LigneCommande;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Achat;
use App\Models\Store;
use App\Models\Category;
use App\Models\CategoryProduct;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LigneCommandeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $proformas = Achat::all();
        //dd($proformas);

        $query = LigneCommande::query();

        if ($request->filled('achat_id')) {
            $query->where('achat_id', $request->input('achat_id'));
        }

        $dataTable = $query->get();

        return view('ligneCommandes.index', compact('dataTable', 'proformas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

   
    public function mergeQuantities(Request $request)
    {
        $commande = LigneCommande::where('invoice_id', $request->invoice_id)
            ->where('item_no', $request->item_no)
            ->first();

        if ($commande) {
            $commande->cartons += $request->cartons;
            $commande->quantity += $request->quantity;
            $commande->total_price += $request->quantity * $request->price;
            $commande->save();
        }

        return response()->json(['success' => true]);
    }


    public function store(Request $request)
    {
        DB::beginTransaction();  // <<< IMPORTANT

        try {
            $validatedData = $request->validate([
                'date_achat' => 'required|date',
                'shippment' => 'required|numeric',
                'store_id' => 'required',
                'products.*.productName' => 'required|string|max:255',
                'products.*.category_id' => 'numeric',
                'products.*.cartons' => 'required|integer|min:1',
                'products.*.unit_price_purchase' => 'required|numeric|min:0',
                'products.*.unit_price_sale' => 'required|numeric|min:0',
                'products.*.price_sale' => 'required|numeric|min:0',
                'products.*.subtotal' => 'required|numeric|min:0',
                'products.*.image' => 'nullable|file|mimes:jpg,png,jpeg,webp',
            ]);

            /**
             * SAFE IDENTIFIER USING LATEST ID
             */
            $lastId = (Achat::latest('id')->value('id') ?? 0) + 1;
            $identifier = "ACHAT" . Carbon::now()->format('Ym') . str_pad($lastId, 4, '0', STR_PAD_LEFT);

            /**
             * GLOBAL TOTALS
             */
            $total_ctns = 0;
            $total_pcs = 0;

            foreach ($validatedData['products'] as $product) {
                $total_ctns += $product['cartons'];
                $total_pcs += $product['cartons'];
            }

            $total_amount = $request->input('total_subtotal', 0);

            /**
             * CREATE ACHAT
             */
            $proforma = Achat::create([
                'store_id' => $request->store_id,
                'identifier' => $identifier,
                'total_ctns' => $total_ctns,
                'total_pcs' => $total_pcs,
                'total_amount' => $total_amount,
                'date_achat' => $request->date_achat,
                'shippment' => $request->shippment,
                'grand_total' => $total_amount,
            ]);

            /**
             * LOOP PRODUCTS
             */
            foreach ($validatedData['products'] as $index => $productData) {

                /** ---- SAFE IMAGE RETRIEVAL ---- */
                $file = $request->file("products.$index.image");
                $productName = "KTC.png"; // default fallback

                if ($file) {
                    $extension = $file->getClientOriginalExtension();
                    $productName = time() . "_product_" . $index . "." . $extension;

                    $file->move(public_path('products'), $productName);
                }

                /** --- FIND OR CREATE PRODUCT --- */
                $product = Product::where('libelle', $productData['productName'])->first();

                if (!$product) {
                    $product = Product::create([
                        'libelle' => $productData['productName'],
                        'sku' => $productData['productName'],
                        'description' => $productData['productName'],
                        'qtityCtn' => 1,
                        'image' => $productName,
                        'price' => $productData['unit_price_purchase'],
                        'price_sale' => $productData['unit_price_sale'],
                        'price_sale_ctn' => $productData['price_sale'],
                    ]);

                    CategoryProduct::create([
                        'category_id' => $productData['category_id'] ?? 1,
                        'product_id' => $product->id,
                    ]);
                } else {
                    // Update product
                    $product->qtityCtn = 1;
                    if ($file) {
                        $product->image = $productName;
                    }
                    $product->save();
                }

                /** ---- CREATE LIGNECOMMANDE ---- */
                $quantity = $productData['cartons'] * 1;
                $montant_sale = $productData['price_sale'] * $productData['cartons'];

                LigneCommande::create([
                    'achat_id' => $proforma->id,
                    'product_id' => $product->id,
                    'cartons' => $productData['cartons'],
                    'quantity' => $quantity,
                    'unit_price_purchase' => $productData['unit_price_purchase'],
                    'unit_price_sale' => $productData['unit_price_sale'],
                    'total_price_purchase' => $productData['subtotal'],
                    'montant_sale' => $montant_sale,
                    'ctn_price_sale' => $productData['price_sale'],
                ]);

                /** ---- UPDATE STOCK ---- */
                StoreProduct::updateOrCreate(
                    [
                        'store_id' => $request->store_id,
                        'product_id' => $product->id,
                    ],
                    [
                        'quantity' => DB::raw("quantity + {$quantity}"),
                        'ctns' => DB::raw("ctns + {$productData['cartons']}"),
                    ]
                );
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json(['message' => 'Achat created successfully.']);
            }

            return redirect()->route('proformas.index')->with('success', 'Achat créée avec succès.');

        } catch (\Throwable $th) {

            DB::rollBack();  // <<< CRITICAL

            if ($request->ajax()) {
                return response()->json(['error' => $th->getMessage()], 500);
            }

            return redirect()->back()->with('error', $th->getMessage());
        }
    }
    
    public function updateInvoice(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'products.*.productName' => 'required|string|max:255',
                'products.*.cartons' => 'required|integer|min:1',
                'products.*.unit_price_purchase' => 'required|numeric|min:0',
                'products.*.unit_price_sale' => 'required|numeric|min:0',
                'products.*.ctn_price_sale' => 'required|numeric|min:0',
                'products.*.subtotal' => 'required|numeric|min:0',
                'products.*.image' => 'image',
            ]);

            $proforma = Achat::findOrFail($id);

            // Recalculate totals
            $total_pcs = 0;
            $total_ctns = 0;
            $total_amount = 0;

            $existingProductIds = [];

            foreach ($validatedData['products'] as $index => $productData) {
                $total_ctns += $productData['cartons'];
                $total_pcs += $productData['cartons'];
                $total_amount += $productData['subtotal'];

                // Check if the product already exists in `LigneCommande`
                $ligneCommande = LigneCommande::where('achat_id', $id)
                    ->where('product_id', Product::where('libelle', $productData['productName'])->first()->id ?? null) // Use existing product ID if available
                    ->first();

                $imagePath = null;

                if ($ligneCommande) {
                    if (!empty($productData['image'])) {
                        // Generate unique filename
                        $fileExtension = $productData['image']->getClientOriginalExtension();
                        $originalPath = $productData['image']->getPathname();
                        $productName = time() . "_product_" . $index . "." . $fileExtension;
                        $compressedPath = public_path("products/{$productName}");

                        // Resize and compress
                        if ($fileExtension === 'jpg' || $fileExtension === 'jpeg') {
                            $image = imagecreatefromjpeg($originalPath);
                        } elseif ($fileExtension === 'png') {
                            $image = imagecreatefrompng($originalPath);
                        } else {
                            throw new \Exception("Invalid image type");
                        }

                        // Resize to max width 500px, keeping aspect ratio
                        list($width, $height) = getimagesize($originalPath);
                        $newWidth = 500;
                        $newHeight = ($height / $width) * $newWidth;

                        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                        // Save compressed image
                        if ($fileExtension === 'jpg' || $fileExtension === 'jpeg') {
                            imagejpeg($resizedImage, $compressedPath, 75); // 75% quality
                            //dd('resized');
                        } elseif ($fileExtension === 'png') {
                            imagepng($resizedImage, $compressedPath, 6); // Compression level 6
                        }

                        // Free memory
                        imagedestroy($image);
                        imagedestroy($resizedImage);
                        $imagePath = $productName;
                    }else{
                        $imagePath = $ligneCommande->product->image;
                    }
                    // Update existing `LigneCommande`
                    $product = Product::where('libelle', $productData['productName'])->first();
                    $reste = $ligneCommande->quantity - $productData['cartons'];
                    $resteCtn = $ligneCommande->cartons - $productData['cartons'];
                    StoreProduct::updateOrCreate(
                        [
                            'store_id' => $proforma->store_id, // Assume store_id is part of the request data
                            'product_id' => $product->id,
                        ],
                        [
                            'quantity' => \DB::raw("quantity - {$reste}"), // Increment the stock
                            'ctns' => \DB::raw("ctns - {$resteCtn}")
                        ]
                    );
                    $ligneCommande->update([
                        'cartons' => $productData['cartons'],
                        'quantity' => $productData['cartons'],
                        'unit_price_purchase' => $productData['unit_price_purchase'],
                        'unit_price_sale' => $productData['unit_price_sale'],
                        'total_price_purchase' => $productData['subtotal'],
                        'montant_sale' => $productData['ctn_price_sale'] * $productData['cartons'],
                        'ctn_price_sale' => $productData['ctn_price_sale']
                    ]);
                    $product->qtityCtn = 1;
                    $product->price = $productData['unit_price_purchase'];
                    $product->price_sale = $productData['unit_price_sale'];
                    $product->price_sale_ctn = $productData['ctn_price_sale'];
                    $product->image = $imagePath;
                    $product->save();
                    
                }
                // Keep track of updated product IDs
                $existingProductIds[] = Product::where('libelle', $productData['productName'])->first()->id ?? $product->id;
            }

            // Delete any `LigneCommande` that was removed
            LigneCommande::where('achat_id', $id)->whereNotIn('product_id', $existingProductIds)->delete();

            // Update `Proforma`
            $proforma->update([
                'total_ctns' => $total_ctns,
                'total_pcs' => $total_pcs,
                'total_amount' => $total_amount,
                'date_achat' => $request->date_achat,
                'shippment' => $request->shippment,
                'grand_total' => $total_amount,
            ]);

            if ($request->ajax()) {
                return response()->json(['message' => 'Achat updated successfully.']);
            } else {
                return redirect()->route('proformas.index')->with('success', 'Achat mis a jour avec succès.');
            }
            //return redirect()->route('proformas.index')->with('success', 'Achat créée avec succès.');
        } catch (\Throwable $th) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Erreur : ' . $th->getMessage()], 500);
            } else {
                return redirect()->back()->with('error', 'Erreur lors de la mise a jour de ce Achat : ' . $th->getMessage());
            }
        }
    }

    public function addInvoice(Request $request, $id)
    {
        session()->regenerate();
        //dd('I arrive here');
        try {
            $validatedData = $request->validate([
                'products.*.productName' => 'required|string|max:255',
                'products.*.cartons' => 'required|integer|min:1',
                'products.*.qty_per_ctn' => 'required|integer|min:1',
                'products.*.price' => 'required|numeric|min:0',
                'products.*.price2' => 'required|numeric|min:0',
                'products.*.weight' => 'required|numeric|min:0',
                'products.*.total_weight' => 'required|numeric|min:0',
                'products.*.subtotal' => 'required|numeric|min:0',
                'products.*.image' => 'nullable|file|mimes:jpg,png,jpeg',
            ]);

            $proforma = Proforma::findOrFail($id);

            // Recalculate totals
            $total_pcs = $proforma->total_pcs ?? 0;
            $total_ctns = $proforma->total_ctns ?? 0;
            $total_weight = $proforma->total_weight ?? 0;
            $total_amount = $proforma->total_amount ?? 0;
            $bonus = $proforma->bonus ?? 0;

            foreach ($validatedData['products'] as $index => $productData) {
                $cartons = $productData['cartons'];
                $qty_per_ctn = $productData['qty_per_ctn'];
                $subtotal = $productData['subtotal'];
                $price2 = $productData['price2'];

                $total_ctns += $cartons;
                $total_pcs += $cartons * $qty_per_ctn;
                $total_weight += $productData['total_weight'];
                $total_amount += $subtotal;
                $bonus += $subtotal - ($price2 * $cartons * $qty_per_ctn);

                // Vérification si le produit existe déjà dans la commande
                $product = Product::where('item_no', $productData['productName'])->first();
                //dd($product);
                if (!$product) {
                    // Créer un nouveau produit si non existant
                    if (!empty($productData['image'])) {
                        // Generate unique filename
                        $fileExtension = $productData['image']->getClientOriginalExtension();
                        $originalPath = $productData['image']->getPathname();
                        $productName = time() . "_product_" . $index . "." . $fileExtension;
                        $compressedPath = public_path("products/{$productName}");

                        // Resize and compress
                        if ($fileExtension === 'jpg' || $fileExtension === 'jpeg') {
                            $image = imagecreatefromjpeg($originalPath);
                        } elseif ($fileExtension === 'png') {
                            $image = imagecreatefrompng($originalPath);
                        } else {
                            throw new \Exception("Invalid image type");
                        }

                        // Resize to max width 500px, keeping aspect ratio
                        list($width, $height) = getimagesize($originalPath);
                        $newWidth = 500;
                        $newHeight = ($height / $width) * $newWidth;

                        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                        // Save compressed image
                        if ($fileExtension === 'jpg' || $fileExtension === 'jpeg') {
                            imagejpeg($resizedImage, $compressedPath, 75); // 75% quality
                            //dd('resized');
                        } elseif ($fileExtension === 'png') {
                            imagepng($resizedImage, $compressedPath, 6); // Compression level 6
                        }

                        // Free memory
                        imagedestroy($image);
                        imagedestroy($resizedImage);
                        $imagePath = $productName;
                    }
                    $product = Product::create([
                        'item_no' => $productData['productName'],
                        'description' => $productData['productName'],
                        'qtityCtn' => $qty_per_ctn,
                        'price' => $productData['price2'],
                        'weight' => $productData['weight'],
                        'image' => $imagePath ?? "21739313776.jpg",
                    ]);
                }else{
                    if (!empty($productData['image'])) {
                        // Generate unique filename
                        $fileExtension = $productData['image']->getClientOriginalExtension();
                        $originalPath = $productData['image']->getPathname();
                        $productName = time() . "_product_" . $index . "." . $fileExtension;
                        $compressedPath = public_path("products/{$productName}");

                        // Resize and compress
                        if ($fileExtension === 'jpg' || $fileExtension === 'jpeg') {
                            $image = imagecreatefromjpeg($originalPath);
                        } elseif ($fileExtension === 'png') {
                            $image = imagecreatefrompng($originalPath);
                        } else {
                            throw new \Exception("Invalid image type");
                        }

                        // Resize to max width 500px, keeping aspect ratio
                        list($width, $height) = getimagesize($originalPath);
                        $newWidth = 500;
                        $newHeight = ($height / $width) * $newWidth;

                        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                        // Save compressed image
                        if ($fileExtension === 'jpg' || $fileExtension === 'jpeg') {
                            imagejpeg($resizedImage, $compressedPath, 75); // 75% quality
                            //dd('resized');
                        } elseif ($fileExtension === 'png') {
                            imagepng($resizedImage, $compressedPath, 6); // Compression level 6
                        }

                        // Free memory
                        imagedestroy($image);
                        imagedestroy($resizedImage);
                        $imagePath = $productName;
                    }
                    $product->weight = $productData['weight'];
                    $product->qtityCtn = $productData['qty_per_ctn'];
                    $product->price = $productData['price2'];
                    $product->image = $imagePath ?? $product->image;
                    $product->save();
                }

                // Vérifier si le produit est déjà dans la proforma
                $ligneCommande = LigneCommande::where('proforma_id', $id)
                    ->where('product_id', $product->id)
                    ->first();

                if ($ligneCommande) {
                    // Mise à jour de la ligne existante
                    $ligneCommande->update([
                        'cartons' => $ligneCommande->cartons + $cartons,
                        'quantity' => $ligneCommande->quantity + ($cartons * $qty_per_ctn),
                        'unit_price' => $productData['price'],
                        'price_shop' => $price2,
                        'total_weight' => $ligneCommande->total_weight + $productData['total_weight'],
                        'total_price' => $ligneCommande->total_price + $subtotal,
                        'montant_shop' => $ligneCommande->montant_shop + ($price2 * $cartons * $qty_per_ctn),
                        'bonus' => $ligneCommande->bonus + ($subtotal - ($price2 * $cartons * $qty_per_ctn))
                    ]);
                } else {
                    // Ajouter une nouvelle ligne
                    LigneCommande::create([
                        'proforma_id' => $proforma->id,
                        'product_id' => $product->id,
                        'cartons' => $cartons,
                        'quantity' => $cartons * $qty_per_ctn,
                        'unit_price' => $productData['price'],
                        'price_shop' => $price2,
                        'total_weight' => $productData['total_weight'],
                        'total_price' => $subtotal,
                        'montant_shop' => $price2 * ($cartons * $qty_per_ctn),
                        'bonus' => $subtotal - ($price2 * $cartons * $qty_per_ctn)
                    ]);
                }
            }
            // Mise à jour des totaux de la proforma
            $commission = ($request->commission_rate * $total_amount) / 100;
            $proforma->update([
                'customer_id' => $request->customer_id,
                'total_ctns' => $total_ctns,
                'total_pcs' => $total_pcs,
                'total_weight' => $total_weight,
                'total_amount' => $total_amount,
                'date_proforma' => $request->date_proforma,
                'commission_rate' => $request->commission_rate,
                'commission' => $commission,
                'bonus_percentage' => $request->bonus_percent,
                'bonus' => $bonus,
                'interest' => $bonus + $commission,
                'shippment' => $request->shippement,
            ]);

            if ($request->ajax()) {
                return response()->json(['message' => 'Achat added successfully.']);
            } else {
                return redirect()->route('proformas.index')->with('success', 'Achat ajoutee avec succès.');
            }
            //return redirect()->route('proformas.index')->with('success', 'Achat créée avec succès.');
        } catch (\Throwable $th) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Erreur : ' . $th->getMessage()], 500);
            } else {
                return redirect()->back()->with('error', 'Erreur lors de l\'ajout d\'elements dans ce Achat : ' . $th->getMessage());
            }
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\LigneCommande  $ligneCommande
     * @return \Illuminate\Http\Response
     */
    public function show(LigneCommande $ligneCommande)
    {
        //
    }

    public function confirmation(Request $request){
        //dd('Ici pour une confirmation');
        //\Log::info($request->all());
        $invoice_id = $request->invoice_id2;
        $commission = $request->commission_percent;
        $invoice = Invoice::find($invoice_id);
        $lineCommandes = LigneCommande::where('invoice_id', $invoice_id);
        $amount_to_pay = $lineCommandes->sum('montant_shop');//234300
        $total_price = $lineCommandes->sum('total_price');//332150
        $total_bonus = $lineCommandes->sum('bonus');//97850
        $commission_amount = $total_price * ($commission / 100);//6643
        $invoice->commission_rate = $request->commission_percent;
        $invoice->commission = $commission_amount;
        $invoice->bonus = $total_bonus;
        $invoice->interest = $invoice->commission + $total_bonus;//104793
        $invoice->amount_to_pay = $amount_to_pay;
        $invoice->amount_to_be_paid = $amount_to_pay + $invoice->interest;
        $invoice->save();
        return redirect()->route('factures.index')->with('success', 'Invoice mis à jour avec succès.');
    }

    public function addLines(Request $request){
        $shops = Store::all();
        //dd($request->invoice_id);
        $invoice_id = $request->invoice_id;
        $invoice = Invoice::find($invoice_id);
        $products = Product::where('store_id', $invoice->store_id)->get();
        //dd($products);
        return view('ligneCommandes.create', compact('invoice_id', 'products', 'shops'))->with('success', 'Veuillez ajouter les lignes de commandes une par une');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\LigneCommande  $ligneCommande
     * @return \Illuminate\Http\Response
     */
    public function edit($ligneCommande)
    {
        $commande = LigneCommande::find($ligneCommande);
        return view('ligneCommandes.edit', compact('commande'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\LigneCommande  $ligneCommande
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $ligneCommande)
    {
        try {
            $commande = LigneCommande::find($ligneCommande);
            $item_no = $request->item_no;
            $product = Product::where('item_no', $item_no)->first();
            $quantity = $product->qtityCtn * $request->cartons;
            $product->price = $request->price;
            $product->save();
            $total_weight = $product->weight * $request->cartons;
            $total_price = $quantity * $product->price;
            $montant_shop = $quantity * $request->price2;
            $bonus = $total_price - $montant_shop;
            $proforma = Proforma::find($commande->proforma_id);
            $diffQtity = $commande->cartons - $request->cartons;
            $newPriceToPay = $product->qtityCtn * $diffQtity * $product->price;
            $newCommission = $newPriceToPay * $proforma->commission_rate * 0.01;
            $bonusSingle = $request->price - $request->price2;
            $newBonus = $commande->bonus - ($bonusSingle * $quantity);
            $newInterest = $newBonus + $newCommission;
            $proforma->total_amount -= $newPriceToPay;
            $proforma->commission -= $newCommission;
            $proforma->bonus -= $newBonus;
            $proforma->interest -= $newInterest;
            $proforma->total_ctns -= $diffQtity;
            $proforma->total_pcs -= $diffQtity * $product->qtityCtn;
            $proforma->save();

            $commande->update([
                'cartons' => $request->cartons,
                'quantity' => $quantity,
                'unit_price' => $request->price,
                'total_price' => $total_price,
                'montant_shop' => $montant_shop,
                'price_shop' => $request->price2,
                'bonus' => $bonus,
            ]);
            return redirect()->route('ligneCommandes.index')->with('success', 'Ligne commande modifiée avec succès');
        } catch (\Throwable $th) {
            return redirect()->route('ligneCommandes.edit', $ligneCommande)->with('error', 'Error message: '. $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\LigneCommande  $ligneCommande
     * @return \Illuminate\Http\Response
     */
    public function destroy($ligneCommandeId)
    {
        //dd($ligneCommandeId);
        // Find the LigneCommande record
        $commande = LigneCommande::findOrFail($ligneCommandeId);

        // Retrieve the associated Proforma record
        $proforma = Achat::findOrFail($commande->achat_id);


        // Calculate the quantities and financial impact
        $cartons = $commande->cartons;
        $quantity = $commande->quantity; // Total Pcs
        $total_price_purchase = $commande->total_price_purchase;
        $montant_sale = $commande->montant_sale;

        // Adjust the Proforma totals
        $proforma->total_amount -= $total_price_purchase;
        $proforma->total_ctns -= $cartons;
        $proforma->total_pcs -= $quantity;
        $proforma->grand_total -= $total_price_purchase;
        $proforma->save();

        // Delete the LigneCommande record
        $commande->delete();

        return redirect()->back()->with('success', 'Order line deleted successfully.');
    }

}
