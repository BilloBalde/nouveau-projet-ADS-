<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    public function index(Request $request)
    {
        $period = $request->input('period', 'daily');
        $now = Carbon::now();

        $user = auth()->user();
        // Emplacements du périmètre de l'utilisateur ; un employé rattaché ne
        // peut pas demander le rapport d'un autre magasin en modifiant l'URL.
        $stores = Store::visibleTo($user)->orderBy('store_name')->get();
        $userStoreId = $user->forcedStoreId();
        $requested = $request->input('store_id');
        $storeId = $requested && $stores->contains('id', (int) $requested)
            ? (int) $requested
            : ($userStoreId ?: $stores->first()?->id);

        [$start, $end, $label] = $this->resolvePeriod($period, $request, $now);

        $salesQuery = Sale::with(['product', 'store'])
            ->whereBetween('created_at', [$start, $end]);

        if ($storeId) {
            $salesQuery->where('store_id', $storeId);
        }

        $sales = $salesQuery->orderBy('created_at')->get();

        $totalQuantity = $sales->sum('quantity');
        $totalAmount = $sales->sum('prixTotal');
        $totalProfit = $sales->sum('interet');

        return view('reports.sales', [
            'sales' => $sales,
            'stores' => $stores,
            'storeId' => $storeId,
            'period' => $period,
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'totalQuantity' => $totalQuantity,
            'totalAmount' => $totalAmount,
            'totalProfit' => $totalProfit,
        ]);
    }

    /**
     * Ventes flash = ventes encaissées au point de vente (factures du canal
     * « pos »). Pour chacune : vendeur, date et heure, emplacement(s),
     * produits, quantités, montant, client. Synthèses par vendeur et par
     * emplacement, sur le jour, la semaine, le mois ou l'année.
     */
    public function flash(Request $request)
    {
        $period = $request->input('period', 'daily');
        [$start, $end, $label] = $this->resolvePeriod($period, $request, Carbon::now());
        $user = auth()->user();

        $lines = \App\Models\Sale::query()
            ->join('factures', 'factures.numero_facture', '=', 'sales.numeroFacture')
            ->whereNull('factures.deleted_at')
            ->where('factures.channel', \App\Models\Facture::CHANNEL_POS)
            ->whereBetween('sales.created_at', [$start, $end])
            ->when($user->isStoreScoped(), fn ($q) => $q->whereIn('sales.store_id', $user->accessibleStoreIds()))
            ->when($request->filled('user_id'), fn ($q) => $q->where('sales.user_id', $request->input('user_id')))
            ->when($request->filled('store_id'), fn ($q) => $q->where('sales.store_id', $request->input('store_id')))
            ->when($request->filled('product_id'), fn ($q) => $q->where('sales.product_id', $request->input('product_id')))
            ->when($request->filled('type'), fn ($q) => $q->whereIn('sales.store_id', Store::where('type', $request->input('type'))->pluck('id')))
            ->select('sales.*', 'factures.customer_id as facture_customer_id', 'factures.id as facture_id');

        $byVendeur = (clone $lines)->reorder()
            ->select(DB::raw('sales.user_id, COUNT(DISTINCT sales.numeroFacture) as nb, SUM(sales.prixTotal) as ca, SUM(sales.quantity) as qty, SUM(sales.interet) as marge'))
            ->groupBy('sales.user_id')->get();
        $byStore = (clone $lines)->reorder()
            ->select(DB::raw('sales.store_id, COUNT(DISTINCT sales.numeroFacture) as nb, SUM(sales.prixTotal) as ca, SUM(sales.quantity) as qty, SUM(sales.interet) as marge'))
            ->groupBy('sales.store_id')->get();

        $details = (clone $lines)
            ->with(['product:id,libelle', 'store:id,store_name,type', 'user:id,name'])
            ->orderByDesc('sales.created_at')
            ->paginate(100)->appends($request->query());

        $customers = \App\Models\Customer::withTrashed()->whereIn('id', $details->pluck('facture_customer_id')->filter()->unique())->pluck('customerName', 'id');
        $userNames = \App\Models\User::whereIn('id', $byVendeur->pluck('user_id')->filter())->pluck('name', 'id');
        $stores = Store::visibleTo($user)->orderBy('store_name')->get();
        $storeNames = Store::whereIn('id', $byStore->pluck('store_id'))->get()->keyBy('id');
        $vendeurs = \App\Models\User::whereIn('id', \App\Models\Sale::visibleTo($user)->distinct()->pluck('user_id')->filter())->orderBy('name')->get(['id', 'name']);
        $products = \App\Models\Product::orderBy('libelle')->get(['id', 'libelle']);

        $totals = [
            'nb'    => (int) $byVendeur->sum('nb'),
            'ca'    => (float) $byVendeur->sum('ca'),
            'qty'   => (int) $byVendeur->sum('qty'),
            'marge' => (float) $byVendeur->sum('marge'),
        ];

        return view('reports.flash', compact(
            'period', 'label', 'start', 'end', 'byVendeur', 'byStore', 'details', 'customers',
            'userNames', 'stores', 'storeNames', 'vendeurs', 'products', 'totals'
        ));
    }

    private function resolvePeriod(string $period, Request $request, Carbon $now): array
    {
        switch ($period) {
            case 'weekly':
                $week = $request->input('week_start', $now->toDateString());
                $start = Carbon::parse($week)->startOfWeek();
                $end = $start->copy()->endOfWeek();
                $label = 'Semaine du ' . $start->format('d/m/Y') . ' au ' . $end->format('d/m/Y');
                break;
            case 'monthly':
                $month = $request->input('month', $now->format('Y-m'));
                $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $end = $start->copy()->endOfMonth();
                $label = $start->translatedFormat('F Y');
                break;
            case 'quarterly':
                $year = (int) $request->input('year', $now->year);
                $quarter = (int) $request->input('quarter', (int) ceil($now->month / 3));
                $startMonth = ($quarter - 1) * 3 + 1;
                $start = Carbon::create($year, $startMonth, 1)->startOfMonth();
                $end = $start->copy()->addMonths(2)->endOfMonth();
                $label = "Trimestre {$quarter} - {$year}";
                break;
            case 'semestral':
                $year = (int) $request->input('year', $now->year);
                $semester = (int) $request->input('semester', $now->month <= 6 ? 1 : 2);
                $startMonth = $semester === 1 ? 1 : 7;
                $start = Carbon::create($year, $startMonth, 1)->startOfMonth();
                $end = $start->copy()->addMonths(5)->endOfMonth();
                $label = "Semestre {$semester} - {$year}";
                break;
            case 'yearly':
                $year = (int) $request->input('year', $now->year);
                $start = Carbon::create($year, 1, 1)->startOfDay();
                $end = Carbon::create($year, 12, 31)->endOfDay();
                $label = "Année {$year}";
                break;
            case 'daily':
            default:
                $date = $request->input('date', $now->toDateString());
                $start = Carbon::parse($date)->startOfDay();
                $end = Carbon::parse($date)->endOfDay();
                $label = $start->toDateString();
                break;
        }

        return [$start, $end, $label];
    }
}
