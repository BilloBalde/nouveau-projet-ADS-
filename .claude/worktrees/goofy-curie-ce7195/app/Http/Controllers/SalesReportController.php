<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

        $storesQuery = Store::query();
        $userStoreId = auth()->user()->role_id == 3
            ? Store::where('user_id', auth()->id())->value('id')
            : null;

        if ($userStoreId) {
            $storesQuery->where('id', $userStoreId);
        }

        $stores = $storesQuery->get();
        $storeId = $userStoreId ?: ($request->input('store_id') ?: $stores->first()?->id);

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

    private function resolvePeriod(string $period, Request $request, Carbon $now): array
    {
        switch ($period) {
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
