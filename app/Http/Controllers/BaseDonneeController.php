<?php

namespace App\Http\Controllers;

use App\Models\CategoryProduct;
use App\Models\Facture;
use App\Models\Logistic;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockTransfer;
use App\Models\StoreProduct;
use App\Models\PaiementJournalier;
use App\Models\Journalier;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BaseDonneeController extends Controller
{
    /**
     * Seules ces tables peuvent être vidées depuis cet écran — évite qu'un {id}
     * arbitraire dans l'URL ne truncate n'importe quelle table de la base.
     */
    private const TRUNCATABLE_TABLES = [
        'category_products', 'products', 'purchases', 'factures', 'expenses',
        'logistics', 'payments', 'sales', 'customers', 'stock_transfers',
        'store_products', 'paiement_journaliers', 'journaliers',
    ];

    public function __construct()
    {
        $this->middleware('auth.check');
        // "Gestion DB" est une page Paramètres : n'importe quel employé connecté
        // pouvait la voir (nombre de lignes par table + bouton de vidage, même si
        // le vidage lui-même était déjà réservé à l'admin ci-dessous).
        $this->middleware('permission:settings.view');
    }
    public function index(){
        $category_products = CategoryProduct::count();
        $factures = Facture::count();
        $logistics = Logistic::count();
        $payments = Payment::count();
        $expenses = Expense::count();
        $products = Product::count();
        $customers = Customer::count();
        $purchases = Purchase::count();
        $sales = Sale::count();
        $stock_transfers = StockTransfer::count();
        $store_products = StoreProduct::count();
        $paiement_journaliers = PaiementJournalier::count();
        $journaliers = Journalier::count();
        $listTables = [
            [$category_products, 'category_products'],
            [$products, 'products'],
            [$purchases, 'purchases'],
            [$factures, 'factures'],
            [$expenses, 'expenses'],
            [$logistics, 'logistics'],
            [$payments, 'payments'],
            [$sales, 'sales'],
            [$customers, 'customers'],
            [$stock_transfers, 'stock_transfers'],
            [$store_products, 'store_products'],
            [$paiement_journaliers, 'paiement_journaliers'],
            [$journaliers, 'journaliers']
        ];

        return view('baseDonne.index', compact('listTables'));
    }
    public function delete($id){
        if ((int) auth()->user()->role_id !== 1) {
            abort(403, 'Seul un administrateur peut vider une table.');
        }

        if (! in_array($id, self::TRUNCATABLE_TABLES, true)) {
            return redirect()->back()->with('error', 'Table invalide.');
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Disable foreign key constraints
            DB::table($id)->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return redirect()->back()->with('success','Table '.$id.' a été vidée avec succès');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Erreur lors de la suppression des données : '.$th->getMessage());
        }
    }
}
