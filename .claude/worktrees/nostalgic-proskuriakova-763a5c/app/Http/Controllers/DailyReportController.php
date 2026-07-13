<?php

namespace App\Http\Controllers;

use App\Models\Achat;
use App\Models\Expense;
use App\Models\Facture;
use App\Models\Payment;
use App\Models\Store;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    public function index(Request $request)
    {
        $data = $this->getReportData($request);
        return view('reports.daily', $data);
    }

    private function resolvePeriod(string $period, Request $request, Carbon $now): array
    {
        switch ($period) {
            case 'weekly':
                $weekStart = $request->input('week_start', $now->copy()->startOfWeek()->toDateString());
                $start = Carbon::parse($weekStart)->startOfDay();
                $end = $start->copy()->addDays(6)->endOfDay();
                $label = $start->toDateString() . ' → ' . $end->toDateString();
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
            case 'semi-annual':
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
            case 'custom':
                $start = Carbon::parse($request->input('start_date', $now->copy()->startOfMonth()->toDateString()))->startOfDay();
                $end = Carbon::parse($request->input('end_date', $now->toDateString()))->endOfDay();
                $label = $start->toDateString() . ' → ' . $end->toDateString();
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

    

    public function exportPDF(Request $request)
    {
        $data = $this->getReportData($request);
        $pdf = PDF::loadView('reports.daily-pdf', $data);
        $filename = 'daily-report-' . str_replace([' ', '→'], ['-', 'to'], $data['label']) . '.pdf';
        
        return $pdf->download($filename);
    }

    private function getReportData(Request $request): array
    {
        $period = $request->input('period', 'daily');
        $now = Carbon::now();

        // Stores available to user
        $storesQuery = Store::query();
        $isVendeur = auth()->user()->role_id == 3;
        $userStoreId = $isVendeur
            ? Store::where('user_id', auth()->id())->value('id')
            : null;

        // Vendeur: only see their store, ignore any store_id from request
        if ($isVendeur && $userStoreId) {
            $storesQuery->where('id', $userStoreId);
            $storeId = $userStoreId; // Force vendeur to only see their store
        } else {
            // Admin: can see all stores or filter by store_id
            $storeId = $request->input('store_id') ?: null;
        }

        $stores = $storesQuery->orderBy('store_name')->get();

        [$start, $end, $label] = $this->resolvePeriod($period, $request, $now);

        // --- Totals (optionally filtered by store) ---
        $achatsQuery = Achat::query()->whereBetween('created_at', [$start, $end]);
        $facturesQuery = Facture::query()->whereBetween('created_at', [$start, $end]);

        if (!empty($storeId)) {
            $achatsQuery->where('store_id', $storeId);
            $facturesQuery->where('store_id', $storeId);
        }

        $totalAchats = (float) $achatsQuery->sum('grand_total');
        $totalVentes = (float) $facturesQuery->sum('montant_total');
        $totalReste = (float) $facturesQuery->sum('reste');
        $totalPayesVentes = (float) $facturesQuery->where('statut', 'payé')->sum('montant_total');

        $paymentsQuery = Payment::query()
            ->whereBetween('payments.created_at', [$start, $end])
            ->join('factures', 'payments.facture_id', '=', 'factures.id');

        if (!empty($storeId)) {
            $paymentsQuery->where('factures.store_id', $storeId);
        }

        $totalEncaisse = (float) $paymentsQuery->sum('payments.versement');

        // Expenses are linked by user_id; we map to stores via stores.user_id
        $expensesQuery = Expense::query()
            ->whereBetween('expenses.created_at', [$start, $end])
            ->join('stores', 'stores.user_id', '=', 'expenses.user_id');

        if (!empty($storeId)) {
            $expensesQuery->where('stores.id', $storeId);
        } else {
            $expensesQuery->whereIn('stores.id', $stores->pluck('id')->all());
        }

        $totalDepenses = (float) $expensesQuery->sum('expenses.amount');

        $profit = $totalVentes - $totalAchats - $totalDepenses;

        // --- Per-store breakdown (for "statistiques complet de chaque boutique") ---
        $storeIds = !empty($storeId)
            ? [(int) $storeId]
            : $stores->pluck('id')->map(fn ($v) => (int) $v)->all();

        $facturesByStore = Facture::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('store_id', $storeIds)
            ->select([
                'store_id',
                DB::raw('COALESCE(SUM(montant_total),0) as total_ventes'),
                DB::raw("COALESCE(SUM(CASE WHEN statut = 'payé' THEN montant_total ELSE 0 END),0) as total_ventes_payees"),
                DB::raw('COALESCE(SUM(reste),0) as total_reste'),
            ])
            ->groupBy('store_id')
            ->get()
            ->keyBy('store_id');

        $achatsByStore = Achat::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('store_id', $storeIds)
            ->select([
                'store_id',
                DB::raw('COALESCE(SUM(grand_total),0) as total_achats'),
            ])
            ->groupBy('store_id')
            ->get()
            ->keyBy('store_id');

        $paymentsByStore = Payment::query()
            ->whereBetween('payments.created_at', [$start, $end])
            ->join('factures', 'payments.facture_id', '=', 'factures.id')
            ->whereIn('factures.store_id', $storeIds)
            ->select([
                'factures.store_id as store_id',
                DB::raw('COALESCE(SUM(payments.versement),0) as total_encaisse'),
            ])
            ->groupBy('factures.store_id')
            ->get()
            ->keyBy('store_id');

        $expensesByStore = Expense::query()
            ->whereBetween('expenses.created_at', [$start, $end])
            ->join('stores', 'stores.user_id', '=', 'expenses.user_id')
            ->whereIn('stores.id', $storeIds)
            ->select([
                'stores.id as store_id',
                DB::raw('COALESCE(SUM(expenses.amount),0) as total_depenses'),
            ])
            ->groupBy('stores.id')
            ->get()
            ->keyBy('store_id');

        // Daily expenses summary (grouped by day)
        // --- Daily expenses summary (grouped by day) ---
        $dailyExpensesQuery = Expense::query()
        ->whereBetween('expenses.created_at', [$start, $end])
        ->join('stores', 'stores.user_id', '=', 'expenses.user_id');

        if (!empty($storeId)) {
        $dailyExpensesQuery->where('stores.id', $storeId);
        } else {
        $dailyExpensesQuery->whereIn('stores.id', $storeIds);
        }

        $dailyExpenses = $dailyExpensesQuery
        ->select([
            DB::raw('DATE(expenses.created_at) as expense_date'),
            DB::raw('COALESCE(SUM(expenses.amount), 0) as total_amount'),
            DB::raw('COUNT(expenses.id) as expense_count'),
        ])
        ->groupBy('expense_date')
        ->orderBy('expense_date', 'desc')
        ->get();

        // --- Per-store breakdown ---
        $breakdown = $stores
        ->filter(fn ($s) => in_array((int) $s->id, $storeIds, true))
        ->map(function ($store) use ($facturesByStore, $achatsByStore, $paymentsByStore, $expensesByStore) {
            $sid = (int) $store->id;
            $ventes = (float) ($facturesByStore[$sid]->total_ventes ?? 0);
            $achats = (float) ($achatsByStore[$sid]->total_achats ?? 0);
            $reste = (float) ($facturesByStore[$sid]->total_reste ?? 0);
            $encaisse = (float) ($paymentsByStore[$sid]->total_encaisse ?? 0);
            $depenses = (float) ($expensesByStore[$sid]->total_depenses ?? 0);

            return [
                'store' => $store,
                'achats' => $achats,
                'ventes' => $ventes,
                'encaisse' => $encaisse,
                'reste' => $reste,
                'depenses' => $depenses,
                'profit' => $ventes - $achats - $depenses,
            ];
        })
        ->values();

        // NE PAS AJOUTER CETTE PARTIE - elle est inutile et peut causer des problèmes
        // $breakdown = collect($breakdown)->map(function ($item) {
        //     return [
        //         'store' => $item['store'] ?? null,
        //         'achats' => $item['achats'] ?? 0,
        //         'ventes' => $item['ventes'] ?? 0,
        //         'encaisse' => $item['encaisse'] ?? 0,
        //         'reste' => $item['reste'] ?? 0,
        //         'depenses' => $item['depenses'] ?? 0,
        //         'profit' => $item['profit'] ?? 0,
        //     ];
        // });

        return [
        'stores' => $stores,
        'storeId' => $storeId ? (int) $storeId : null,
        'period' => $period,
        'label' => $label,
        'start' => $start,
        'end' => $end,
        'totalAchats' => $totalAchats,
        'totalVentes' => $totalVentes,
        'totalPayesVentes' => $totalPayesVentes,
        'totalEncaisse' => $totalEncaisse,
        'totalReste' => $totalReste,
        'totalDepenses' => $totalDepenses,
        'profit' => $profit,
        'breakdown' => $breakdown,
        'dailyExpenses' => $dailyExpenses,
        ];
    }

    
}

