<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Facture;
use App\Models\Logistic;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StoreProduct;
use App\Models\Achat;
use App\Models\LigneCommande;
use App\Models\Store;
use App\Models\Sale;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class IndexController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }
    public function index(Request $request)
    {
        if (auth()->user()->role_id == 3) {
            $userStoreId = auth()->user()->id;
            try {
                $store_id = Store::where('user_id', $userStoreId)->first()->id;
            } catch (\Throwable $th) {
                return redirect('login')->with('error','Dites au manager de vous attribuer à une boutique afin de pouvoir continuer');
            }
            $total_credits = Customer::where('balance', '>', 0)->sum('balance');
            $total_purchases = Achat::where('store_id', $store_id)
                                ->selectRaw('SUM(grand_total) as total')
                                ->value('total');
            $total_sales = Sale::where('store_id', $store_id)
                ->whereDate('created_at', Carbon::today())
                ->sum('prixTotal');
            $total_sales_paid = Payment::whereHas('facture', function ($q) use ($store_id) {
                $q->where('store_id', $store_id);
            })->sum('versement');
            $solde_orange_money = Payment::whereHas('facture', function ($q) use ($store_id) {
                $q->where('store_id', $store_id);
            })->where('paid_by', 'orange money')->sum('versement');
            $solde_cash = Payment::whereHas('facture', function ($q) use ($store_id) {
                $q->where('store_id', $store_id);
            })->where('paid_by', 'cash')->sum('versement');
            $total_expenses = Expense::sum('amount');
            $total_customers = Customer::count();
            $total_quantities = StoreProduct::where('store_id', $store_id)->sum('quantity');
            $total_purchase_invoices = Achat::where('store_id', $store_id)->count();
            $total_sales_invoices = Facture::where('store_id', $store_id)->count();
            $latestPurchases = Achat::where('store_id', $store_id)->select('achats.*')
            ->join(DB::raw('(SELECT MAX(id) as id FROM achats) as latest_purchases'), 'achats.id', '=', 'latest_purchases.id')
            ->orderBy('achats.created_at', 'desc')
            ->take(5)
            ->get();
            $latestSales = Sale::where('store_id', $store_id)->with('product')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            $salesData = DB::table('factures')
                ->where('store_id', $store_id)
                ->select(DB::raw('SUM(montant_total) as total'), DB::raw('MONTH(created_at) as month'))
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $purchasesData = DB::table('achats')
                ->where('store_id', $store_id)
                ->select(DB::raw('SUM(grand_total) as total'), DB::raw('MONTH(created_at) as month'))
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();
            $gains = Sale::where('store_id', $store_id)->sum('interet');
            $total_gains_all = $gains;
            // Get data for each month
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $sales = [];
            $purchases = [];

            foreach (range(1, 12) as $month) {
                $sales[] = $salesData[$month] ?? 0;
                $purchases[] = -($purchasesData[$month] ?? 0); // Negate purchase values for the chart
            }
        }else{
            $total_credits = Customer::where('balance', '>', 0)->sum('balance');
            $total_purchases = Achat::selectRaw('SUM(grand_total) as total')
                                ->value('total');
            $total_sales = Sale::whereDate('created_at', Carbon::today())
                ->sum('prixTotal');
            $total_sales_paid = Payment::sum('versement');
            $solde_orange_money = Payment::where('paid_by', 'orange money')->sum('versement');
            $solde_cash = Payment::where('paid_by', 'cash')->sum('versement');
            $total_expenses = Expense::sum('amount');
            $total_customers = Customer::count();
            $total_quantities = StoreProduct::sum('quantity');
            $total_purchase_invoices = Achat::count();
            $total_sales_invoices = Facture::count();
            $latestPurchases = Achat::select('achats.*')
            ->join(DB::raw('(SELECT MAX(id) as id FROM achats) as latest_purchases'), 'achats.id', '=', 'latest_purchases.id')
            ->orderBy('achats.created_at', 'desc')
            ->take(5)
            ->get();
            $latestSales = Sale::with('product')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            $salesData = DB::table('factures')
                ->select(DB::raw('SUM(montant_total) as total'), DB::raw('MONTH(created_at) as month'))
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $purchasesData = DB::table('achats')
                ->select(DB::raw('SUM(grand_total) as total'), DB::raw('MONTH(created_at) as month'))
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();
            $gains = Sale::sum('interet');
            $total_gains_all = $gains;
            // Get data for each month
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $sales = [];
            $purchases = [];

            foreach (range(1, 12) as $month) {
                $sales[] = $salesData[$month] ?? 0;
                $purchases[] = -($purchasesData[$month] ?? 0); // Negate purchase values for the chart
            }
        }
        $customers = Customer::all();
        $query = Facture::query();

        if ($request->filled('customer_id')) {
            $query->where('customer_id', 'like', '%' . $request->customer_id . '%');
        }
        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->created_at);
        }

        if (auth()->user()->role_id == 3) {
            $store = Store::where('user_id', auth()->id())->first();
            $query->where('store_id', $store->id);
        }
        
        $factures = $query->with('paiements')->get();

        // Group by customer + date (as Y-m-d)
        $grouped = $factures->groupBy(function ($facture) {
            return $facture->customer_id . '|' . $facture->created_at->format('Y-m-d');
        });
        
        return view('index', compact('latestPurchases', 'latestSales', 'total_purchases', 'total_sales', 'total_sales_paid', 'solde_orange_money', 'solde_cash', 'total_expenses', 'total_customers', 'total_quantities', 'total_purchase_invoices', 'total_sales_invoices', 'total_credits', 'gains', 'total_gains_all', 'sales', 'purchases', 'months', 'grouped', 'customers'));
    }
}
