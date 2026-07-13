<?php
// app/Http/Controllers/MovementController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Achat;
use App\Models\Company; // en haut du fichier
use App\Models\Expense;
use App\Models\Sale;
use App\Models\Store;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class MovementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && in_array($user->role, ['admin', 'superuser']);

        // Si l'utilisateur est un vendeur, filtre uniquement sa boutique
        $stores = $isAdmin ? Store::orderBy('store_name')->get() : collect(); // Les admins et superusers voient toutes les boutiques

        // ------------------------------------------------------------
        // 1. VENTES
        // ------------------------------------------------------------
        $salesQuery = Sale::with(['product', 'store'])
            ->when(!$isAdmin, fn($q) => $q->where('store_id', $user->store_id)); // Les vendeurs voient leur propre boutique uniquement

        // ------------------------------------------------------------
        // 2. ACHATS
        // ------------------------------------------------------------
        $achatsQuery = Achat::with(['store']);
        if (method_exists(Achat::class, 'product')) {
            $achatsQuery->with('product');
        }
        if (method_exists(Achat::class, 'products')) {
            $achatsQuery->with('products');
        }
        $achatsQuery->when(!$isAdmin, fn($q) => $q->where('store_id', $user->store_id)); // Idem pour les achats

        // ------------------------------------------------------------
        // 3. DÉPENSES
        // ------------------------------------------------------------
        $expensesQuery = Expense::with(['category', 'store'])
            ->when(!$isAdmin, fn($q) => $q->where('store_id', $user->store_id)); // Idem pour les dépenses

        // 🔍 FILTRES
        if ($request->filled('type')) {
            switch ($request->type) {
                case 'sale':
                    $achatsQuery->whereRaw('1=0');
                    $expensesQuery->whereRaw('1=0');
                    break;
                case 'purchase':
                    $salesQuery->whereRaw('1=0');
                    $expensesQuery->whereRaw('1=0');
                    break;
                case 'expense':
                    $salesQuery->whereRaw('1=0');
                    $achatsQuery->whereRaw('1=0');
                    break;
            }
        }

        if ($isAdmin && $request->filled('store_id')) {
            // Si l'utilisateur est admin, il peut voir toutes les boutiques
            $salesQuery->where('store_id', $request->store_id);
            $achatsQuery->where('store_id', $request->store_id);
            $expensesQuery->where('store_id', $request->store_id);
        }

        if ($isAdmin && $request->filled('store_id')) {
            $salesQuery->where('store_id', $request->store_id);
            $achatsQuery->where('store_id', $request->store_id);
            $expensesQuery->where('store_id', $request->store_id);
        }

        if ($request->filled('date_debut')) {
            $salesQuery->whereDate('created_at', '>=', $request->date_debut);
            $achatsQuery->whereDate('created_at', '>=', $request->date_debut);
            $expensesQuery->whereDate('created_at', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $salesQuery->whereDate('created_at', '<=', $request->date_fin);
            $achatsQuery->whereDate('created_at', '<=', $request->date_fin);
            $expensesQuery->whereDate('created_at', '<=', $request->date_fin);
        }

        if ($request->filled('reference')) {
            $ref = $request->reference;
            $salesQuery->where('numeroFacture', 'like', "%{$ref}%");
            $achatsQuery->where('identifier', 'like', "%{$ref}%");
            $expensesQuery->where('reference', 'like', "%{$ref}%");
        }

        // ------------------------------------------------------------
        // 4. TRANSFORMATION – AVEC "use ($isAdmin, $user)"
        // ------------------------------------------------------------
        $sales = $salesQuery->get()->map(fn($sale) => [
            'id'          => $sale->id,
            'type'        => 'Vente',
            'type_code'   => 'sale',
            'reference'   => $sale->numeroFacture ?? 'VTE_'.$sale->id,
            'date'        => $sale->created_at,
            'montant'     => $sale->prixTotal ?? 0,
            'produit_nom' => $sale->product->libelle ?? $sale->product->name ?? 'Produit inconnu',
            'produit_qte' => $sale->quantity ?? 0,
            'details'     => 'Qté: '.($sale->quantity ?? 0),
            'store_id'    => $sale->store_id,
            'store_name'  => $sale->store->store_name ?? $sale->store->name ?? 'N/A',
            'can_edit'    => $isAdmin || ($user->store_id == $sale->store_id),
        ]);

        $achats = $achatsQuery->get()->map(function ($achat) use ($isAdmin, $user) { // ✅ USE ici
            $productName = 'Achat global';
            $quantity    = $achat->total_pcs ?? 1;
            $details     = 'Qté: '.($achat->total_pcs ?? '');

            if (method_exists($achat, 'product') && $achat->product) {
                $productName = $achat->product->libelle ?? $achat->product->name ?? 'Produit inconnu';
                $quantity    = $achat->quantity ?? 1;
                $details     = 'Qté: '.($achat->quantity ?? 1);
            } elseif (method_exists($achat, 'products') && $achat->products && $achat->products->isNotEmpty()) {
                $productName = 'Achat multiple ('.count($achat->products).' produits)';
                $quantity    = $achat->products->sum('pivot.quantity');
                $details     = 'Qté totale: '.$quantity;
            }

            return [
                'id'          => $achat->id,
                'type'        => 'Achat',
                'type_code'   => 'purchase',
                'reference'   => $achat->identifier ?? 'ACH_'.$achat->id,
                'date'        => $achat->created_at,
                'montant'     => $achat->grand_total ?? 0,
                'produit_nom' => $productName,
                'produit_qte' => $quantity,
                'details'     => $details,
                'store_id'    => $achat->store_id,
                'store_name'  => $achat->store->store_name ?? $achat->store->name ?? 'N/A',
                'can_edit'    => $isAdmin || ($user->store_id == $achat->store_id),
            ];
        });

        $expenses = $expensesQuery->get()->map(function ($expense) use ($isAdmin, $user) { // ✅ USE ici
            return [
                'id'          => $expense->id,
                'type'        => 'Dépense',
                'type_code'   => 'expense',
                'reference'   => $expense->reference,
                'date'        => $expense->created_at,
                'montant'     => $expense->amount ?? 0,
                'produit_nom' => '—',
                'produit_qte' => null,
                'details'     => $expense->category->categoryName ?? 'N/A',
                'store_id'    => $expense->store_id,
                'store_name'  => $expense->store->store_name ?? $expense->store->name ?? 'N/A',
                'can_edit'    => $isAdmin || ($user->store_id == $expense->store_id),
            ];
        });

        // ------------------------------------------------------------
        // 5. FUSION, TRI, PAGINATION
        // ------------------------------------------------------------
        $movements = collect()
            ->merge($sales)
            ->merge($achats)
            ->merge($expenses)
            ->sortByDesc('date')
            ->values();

        $perPage = 15;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $movements->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginated = new LengthAwarePaginator(
            $currentItems,
            $movements->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // ------------------------------------------------------------
        // 6. STATISTIQUES
        // ------------------------------------------------------------
        $totalVentes   = $sales->sum('montant');
        $totalAchats   = $achats->sum('montant');
        $totalDepenses = $expenses->sum('montant');
        $solde         = $totalVentes - $totalAchats - $totalDepenses;

        return view('movements.index', compact(
            'paginated',
            'totalVentes',
            'totalAchats',
            'totalDepenses',
            'solde',
            'isAdmin',
            'user', // utile pour la vue si besoin
            'stores' // pour le dropdown de sélection de magasin (admin uniquement)
        ));
    }


    public function exportPdf(Request $request)
    {
        // Garde-fou mémoire : un export non filtré peut porter sur des milliers
        // de lignes (ventes + achats + dépenses) et Dompdf est gourmand en RAM.
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);

        // Si aucune période n'est précisée, se limiter au mois en cours par défaut
        // pour éviter d'exporter tout l'historique en un seul PDF.
        if (!$request->filled('date_debut') && !$request->filled('date_fin')) {
            $request->merge([
                'date_debut' => now()->startOfMonth()->format('Y-m-d'),
                'date_fin'   => now()->endOfMonth()->format('Y-m-d'),
            ]);
        }

        $user = auth()->user();
        $isAdmin = $user && in_array($user->role, ['admin', 'superuser']);

        // 1. VENTES
        $salesQuery = Sale::with(['product', 'store'])
            ->when(!$isAdmin, fn($q) => $q->where('store_id', $user->store_id)); // Filtrer les ventes pour les vendeurs

        // 2. ACHATS
        $achatsQuery = Achat::with(['store']);
        if (method_exists(Achat::class, 'product')) {
            $achatsQuery->with('product');
        }
        if (method_exists(Achat::class, 'products')) {
            $achatsQuery->with('products');
        }
        $achatsQuery->when(!$isAdmin, fn($q) => $q->where('store_id', $user->store_id)); // Filtrer les achats pour les vendeurs

        // 3. DÉPENSES
        $expensesQuery = Expense::with(['category', 'store'])
            ->when(!$isAdmin, fn($q) => $q->where('store_id', $user->store_id)); // Filtrer les dépenses pour les vendeurs

        // 🔍 FILTRES
        if ($request->filled('date_debut')) {
            $salesQuery->whereDate('created_at', '>=', $request->date_debut);
            $achatsQuery->whereDate('created_at', '>=', $request->date_debut);
            $expensesQuery->whereDate('created_at', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $salesQuery->whereDate('created_at', '<=', $request->date_fin);
            $achatsQuery->whereDate('created_at', '<=', $request->date_fin);
            $expensesQuery->whereDate('created_at', '<=', $request->date_fin);
        }
        if ($request->filled('type')) {
            if ($request->type != 'sale')     $salesQuery->whereRaw('1=0');
            if ($request->type != 'purchase') $achatsQuery->whereRaw('1=0');
            if ($request->type != 'expense')  $expensesQuery->whereRaw('1=0');
        }
        if ($request->filled('reference')) {
            $search = $request->reference;
            $salesQuery->where('numeroFacture', 'like', "%$search%");
            $achatsQuery->where('identifier', 'like', "%$search%");
            $expensesQuery->where('reference', 'like', "%$search%");
        }

        // ------------------------------------------------------------
        // 4. TRANSFORMATION (COPIEZ INTÉGRALEMENT DEPUIS VOTRE INDEX)
        // ------------------------------------------------------------
        // VENTES
        $sales = $salesQuery->get()->map(fn($sale) => [
            'id'          => $sale->id,
            'type'        => 'Vente',
            'type_code'   => 'sale',
            'reference'   => $sale->numeroFacture ?? 'VTE_'.$sale->id,
            'date'        => $sale->created_at, // ✅ clé date définie
            'montant'     => $sale->prixTotal ?? 0,
            'produit_nom' => $sale->product->libelle ?? 'Produit inconnu',
            'produit_qte' => $sale->quantity ?? 0,
            'details'     => 'Qté: '.($sale->quantity ?? 0),
            'store_id'    => $sale->store_id,
            'store_name'  => $sale->store->store_name ?? 'N/A',
        ]);


            // Récupérer les informations de l'entreprise
            $company = Company::first() ?? (object) [
                'name'    => config('app.name'),
                'address' => 'Adresse non renseignée',
                'phone'   => 'N/A',
                'email'   => 'N/A',
                'logo'    => 'assets/img/logo.png',
            ];

        // ACHATS
        $achats = $achatsQuery->get()->map(function ($achat) {
            $productName = 'Achat global';
            $quantity    = $achat->total_pcs ?? 1;
            $details     = 'Qté: '.($achat->total_pcs ?? '');

            if (method_exists($achat, 'product') && $achat->product) {
                $productName = $achat->product->libelle ?? 'Produit inconnu';
                $quantity    = $achat->quantity ?? 1;
                $details     = 'Qté: '.($achat->quantity ?? 1);
            } elseif (method_exists($achat, 'products') && $achat->products && $achat->products->isNotEmpty()) {
                $productName = 'Achat multiple';
                $quantity    = $achat->products->sum('pivot.quantity');
                $details     = 'Qté totale: '.$quantity;
            }

            return [
                'id'          => $achat->id,
                'type'        => 'Achat',
                'type_code'   => 'purchase',
                'reference'   => $achat->identifier ?? 'ACH_'.$achat->id,
                'date'        => $achat->created_at, // ✅ clé date définie
                'montant'     => $achat->grand_total ?? 0,
                'produit_nom' => $productName,
                'produit_qte' => $quantity,
                'details'     => $details,
                'store_id'    => $achat->store_id,
                'store_name'  => $achat->store->store_name ?? 'N/A',
            ];
        });

        // DÉPENSES
        $expenses = $expensesQuery->get()->map(fn($expense) => [
            'id'          => $expense->id,
            'type'        => 'Dépense',
            'type_code'   => 'expense',
            'reference'   => $expense->reference,
            'date'        => $expense->created_at, // ✅ clé date définie
            'montant'     => $expense->amount ?? 0,
            'produit_nom' => '—',
            'produit_qte' => null,
            'details'     => $expense->category->categoryName ?? 'N/A',
            'store_id'    => $expense->store_id,
            'store_name'  => $expense->store->store_name ?? 'N/A',
        ]);

        // ------------------------------------------------------------
        // 5. FUSION, TRI
        // ------------------------------------------------------------
        $movements = collect()
            ->merge($sales)
            ->merge($achats)
            ->merge($expenses)
            ->sortByDesc('date')
            ->values();

        // ------------------------------------------------------------
        // 6. STATISTIQUES
        // ------------------------------------------------------------
        $totalVentes   = $sales->sum('montant');
        $totalAchats   = $achats->sum('montant');
        $totalDepenses = $expenses->sum('montant');
        $solde         = $totalVentes - $totalAchats - $totalDepenses;

        // ------------------------------------------------------------
        // 7. GÉNÉRATION PDF
        // ------------------------------------------------------------
        $pdf = Pdf::loadView('movements.pdf', compact(
            'movements',
            'totalVentes',
            'totalAchats',
            'totalDepenses',
            'solde',
            'isAdmin',
            'request',
            'company'
        ));

        $fileName = 'mouvements_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($fileName);
    }
}