<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Facture;
use App\Models\Fund;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Services\FundService;
use App\Services\InventaireRapportService;
use App\Support\ModeDeVente;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    /**
     * Total des achats, toutes sources confondues : `achats` (module Proforma/
     * LigneCommande) est vide dans ce projet — les achats réels sont soit dans
     * `purchases` (ancien flux simple), soit dans `purchase_orders` (module Achat
     * actuel, avec coût de revient atterri). Se limiter à Achat::sum('grand_total')
     * donnait donc 0 partout sur le dashboard, quelle que soit l'activité réelle.
     */
    private function totalPurchases(?int $storeId = null): float
    {
        $fromPurchases = Purchase::when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->selectRaw('SUM(price * quantity) as total')
            ->value('total');

        $fromOrders = PurchaseOrder::where('status', '!=', 'cancelled')
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->sum('total_landed_cost_gnf');

        return (float) $fromPurchases + (float) $fromOrders;
    }

    /**
     * Numéro du mois (1-12) d'une colonne date, dans le dialecte du serveur.
     *
     * MONTH() est propre à MySQL : sous SQLite — le moteur de la suite de
     * tests — la requête échoue avec « no such function: MONTH », ce qui
     * rendait le tableau de bord intestable, donc sans filet de sécurité
     * alors que c'est l'écran le plus consulté.
     *
     * $colonne est un nom de colonne écrit dans ce fichier, jamais une valeur
     * venant d'une requête HTTP.
     */
    private function moisSql(string $colonne): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', $colonne) AS INTEGER)"
            : "MONTH($colonne)";
    }

    /**
     * Même total que totalPurchases(), réparti par mois (1-12) pour le graphique
     * "Ventes & Achats".
     *
     * Le graphique est étiqueté Jan…Déc : il porte sur une seule année, d'où le
     * filtre. Sans lui, le groupement par mois additionnait janvier de toutes
     * les années confondues — invisible la première année, faux dès la seconde.
     */
    private function monthlyPurchases(?int $storeId = null, ?int $annee = null): array
    {
        $annee ??= Carbon::now()->year;
        $mois = $this->moisSql('created_at');

        $fromPurchases = DB::table('purchases')
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->whereYear('created_at', $annee)
            ->selectRaw("SUM(price * quantity) as total, $mois as month")
            ->groupBy('month')
            ->pluck('total', 'month');

        $fromOrders = DB::table('purchase_orders')
            ->where('status', '!=', 'cancelled')
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->whereYear('created_at', $annee)
            ->selectRaw("SUM(total_landed_cost_gnf) as total, $mois as month")
            ->groupBy('month')
            ->pluck('total', 'month');

        $combined = [];
        foreach (range(1, 12) as $month) {
            $combined[$month] = (float) ($fromPurchases[$month] ?? 0) + (float) ($fromOrders[$month] ?? 0);
        }

        return $combined;
    }

    /**
     * Chiffres multi-emplacements du tableau de bord. Requêtes agrégées : le
     * coût ne dépend pas du nombre de magasins ou d'entrepôts.
     */
    private function locationOverview($user): array
    {
        $scope = fn ($q, $column = 'store_id') => $user->isStoreScoped() ? $q->whereIn($column, $user->accessibleStoreIds()) : $q;
        $warehouseIds = Store::where('type', Store::TYPE_ENTREPOT)->pluck('id');

        $periods = [
            'jour' => [Carbon::today(), Carbon::today()->endOfDay()],
            'semaine' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'mois' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'annee' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
        ];

        // Les quatre périodes en UNE requête. Chacune demandait trois agrégats,
        // soit douze allers-retours pour des chiffres lus sur la même table.
        // Les bornes sont des dates calculées, jamais une saisie : pas de
        // valeur à lier dans le CASE.
        $colonnes = [];
        foreach ($periods as $key => [$debut, $fin]) {
            $entre = "created_at BETWEEN '".$debut->toDateTimeString()."' AND '".$fin->toDateTimeString()."'";
            $colonnes[] = "COALESCE(SUM(CASE WHEN $entre THEN prixTotal END),0) as {$key}_ca";
            $colonnes[] = "COALESCE(SUM(CASE WHEN $entre THEN interet END),0) as {$key}_marge";
            $colonnes[] = "COALESCE(SUM(CASE WHEN $entre AND fund_id IS NOT NULL THEN prixTotal END),0) as {$key}_assoc_ca";
            $colonnes[] = "COALESCE(SUM(CASE WHEN $entre AND fund_id IS NOT NULL THEN interet END),0) as {$key}_assoc_marge";
            $colonnes[] = $warehouseIds->isEmpty()
                ? "0 as {$key}_direct"
                : "COALESCE(SUM(CASE WHEN $entre AND store_id IN (".$warehouseIds->implode(',').") THEN prixTotal END),0) as {$key}_direct";
        }

        $agregats = $scope(Sale::query())
            ->whereBetween('created_at', [$periods['annee'][0], $periods['annee'][1]])
            ->selectRaw(implode(', ', $colonnes))
            ->first();

        $sales = [];
        foreach (array_keys($periods) as $key) {
            $sales[$key] = [
                'ca' => (float) $agregats->{$key.'_ca'},
                'marge' => (float) $agregats->{$key.'_marge'},
                'direct' => (float) $agregats->{$key.'_direct'},
                // Part portant sur des marchandises financées par des associés
                // (facturée ; leur résultat à partager est dans leurs relevés).
                'assoc_ca' => (float) $agregats->{$key.'_assoc_ca'},
                'assoc_marge' => (float) $agregats->{$key.'_assoc_marge'},
            ];
        }

        // Synthèse des associés, pour le patron uniquement.
        $associates = null;
        if ($user->hasPermission('associates.manage') && Fund::exists()) {
            $service = app(FundService::class);
            $releves = Fund::where('active', true)->get()->map(fn ($f) => $service->statement($f));
            $associates = [
                'count' => $releves->count(),
                'capital_restant' => (float) $releves->sum('capital_restant'),
                'stock_valeur' => (float) $releves->sum('stock_valeur'),
                'benefice_a_verser' => (float) $releves->sum('benefice_a_verser'),
                'part_patron_restante' => (float) $releves->sum('part_patron_restante'),
                'tresorerie' => (float) $releves->sum('tresorerie'),
            ];
        }

        // Stock par type d'emplacement, détaillé par unité (cartons, pièces…) :
        // on n'additionne pas des unités différentes.
        $stockByType = $scope(StoreProduct::query()->join('stores', 'stores.id', '=', 'store_products.store_id'), 'store_products.store_id')
            ->join('products', 'products.id', '=', 'store_products.product_id')
            ->selectRaw("stores.type as type, COALESCE(products.unite, 'pcs') as unite, SUM(store_products.quantity) as qty")
            ->groupBy('stores.type', DB::raw("COALESCE(products.unite, 'pcs')"))
            ->get()
            ->groupBy('type')
            ->map(fn ($lignes) => Product::resumeQuantites($lignes));

        $pendingTransfers = StockTransfer::visibleTo($user)
            ->whereIn('status', [StockTransfer::PENDING, StockTransfer::APPROVED, StockTransfer::IN_TRANSIT])
            ->count();

        return [
            'sales' => $sales,
            'stock_by_type' => $stockByType,
            'pending_transfers' => $pendingTransfers,
            'locations' => Store::visibleTo($user)->count(),
            'associates' => $associates,
        ];
    }

    /**
     * Produits dont le stock total est au plus bas, toutes boutiques
     * confondues. Partagée par les deux tableaux de bord.
     *
     * Le seuil se pose sur la sous-requête elle-même, pas sur son alias par
     * HAVING : HAVING sans GROUP BY est accepté par MySQL mais refusé par
     * d'autres moteurs (« HAVING clause on a non-aggregate query »), ce qui
     * interdisait tout test de cet écran.
     */
    private function alertesDeStock(int $seuil = 100, int $limite = 100)
    {
        $stockReel = DB::table('store_products')
            ->selectRaw('COALESCE(SUM(store_products.quantity), 0)')
            ->whereColumn('store_products.product_id', 'products.id');

        return Product::query()
            ->select(['id', 'libelle', 'unite'])
            ->selectSub($stockReel, 'stock_total')
            ->where($stockReel, '<=', $seuil)
            ->orderBy('stock_total')
            ->take($limite)
            ->get();
    }

    /**
     * Tableau de bord d'une installation qui compte son stock au lieu de
     * saisir ses ventes : les chiffres viennent des séances d'inventaire
     * validées, pas des ventes.
     */
    /**
     * Les douze mois de l'année en cours : étiquettes, ventes, achats.
     *
     * Les achats étaient renvoyés en négatif, ce qui retournait leurs barres
     * sous l'axe : deux mesures du même signe n'ont pas à se faire face. Et
     * les étiquettes étaient en anglais au milieu d'une application française.
     *
     * @param  array<int, float>  $ventesParMois
     * @param  array<int, float>  $achatsParMois
     * @return array{0: array<int, string>, 1: array<int, float|null>, 2: array<int, float|null>}
     */
    private function courbesDeLAnnee(array $ventesParMois, array $achatsParMois): array
    {
        $annee = (int) Carbon::now()->year;
        $ventes = [];
        $achats = [];

        foreach (range(1, 12) as $mois) {
            $ventes[$mois] = (float) ($ventesParMois[$mois] ?? 0);
            $achats[$mois] = (float) ($achatsParMois[$mois] ?? 0);
        }

        return [
            ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
            array_values($this->moisAVenirVides($ventes, $annee)),
            array_values($this->moisAVenirVides($achats, $annee)),
        ];
    }

    /**
     * Les mois qui ne sont pas encore arrivés valent null, pas zéro.
     *
     * Un mois à venir n'a pas fait zéro de chiffre d'affaires : il n'a pas de
     * chiffre. Tracé à zéro, il dessinait une chute verticale en fin d'année —
     * une faillite imaginaire. En null, la courbe s'arrête simplement.
     *
     * @param  array<int, float>  $parMois
     * @return array<int, float|null>
     */
    private function moisAVenirVides(array $parMois, int $annee): array
    {
        $mois = (int) Carbon::now()->month;

        if ($annee !== (int) Carbon::now()->year || $mois >= 12) {
            return $parMois;
        }

        foreach (range($mois + 1, 12) as $aVenir) {
            $parMois[$aVenir] = null;
        }

        return $parMois;
    }

    private function tableauDeBordInventaire($user)
    {
        $scopeIds = $user->isStoreScoped() ? $user->accessibleStoreIds() : null;
        $rapport = app(InventaireRapportService::class);

        $chiffres = $rapport->tableauDeBord($scopeIds);
        $annee = $chiffres['annee'];

        $achatsParMois = $this->monthlyPurchases($user->isStoreScoped() ? $user->assignedStoreId() : null, $annee);

        // Les deux courbes s'arrêtent au mois en cours, elles ne retombent pas
        // à zéro. Voir moisAVenirVides().
        $chiffres['ca_par_mois'] = $this->moisAVenirVides($chiffres['ca_par_mois'], $annee);

        return view('index-inventaire', $chiffres + [
            'meilleuresVentes' => $rapport->meilleuresVentes(
                $scopeIds,
                Carbon::create($annee, 1, 1)->startOfDay(),
                Carbon::create($annee, 12, 31)->endOfDay()
            ),
            'achats_par_mois' => $this->moisAVenirVides($achatsParMois, $annee),
            'total_achats' => $this->totalPurchases($user->isStoreScoped() ? $user->assignedStoreId() : null),
            'total_depenses' => Expense::when($scopeIds !== null, fn ($q) => $q->whereIn('store_id', $scopeIds))->sum('amount'),
            'total_stock' => Product::resumeStock($scopeIds),
            'stockAlerts' => $this->alertesDeStock(),
            'mois' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],

            // Le mois courant, mis en avant sous le chiffre de l'année : c'est
            // celui que le gérant suit au jour le jour. Il n'est montré que si
            // l'année affichée est l'année en cours — sur un historique, « en
            // octobre » n'aurait pas de sens.
            'ca_mois_en_cours' => $annee === (int) Carbon::now()->year
                ? (float) ($chiffres['ca_par_mois'][Carbon::now()->month] ?? 0)
                : 0.0,
            'mois_en_cours_libelle' => $annee === (int) Carbon::now()->year
                ? Carbon::now()->locale('fr')->translatedFormat('F')
                : null,
        ]);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        // Quand le chiffre d'affaires se déduit des comptages de stock, les
        // écrans de la caisse n'ont rien à montrer : la table des ventes est
        // vide par construction. Ce tableau de bord-là lirait zéro partout
        // alors que la boutique a vendu. Il a donc le sien.
        if (ModeDeVente::parInventaire()) {
            return $this->tableauDeBordInventaire($user);
        }
        if ($user->isStoreScoped()) {
            // Magasin d'affectation (users.store_id) et emplacements rattachés.
            $store_id = $user->assignedStoreId();
            $scopeIds = $user->accessibleStoreIds();
            if (! $store_id) {
                // Se déconnecter avant de rediriger vers /login est indispensable :
                // sinon Authentification::login() voit un utilisateur toujours
                // authentifié et le renvoie directement vers /home, qui échoue de
                // nouveau ici — boucle de redirection infinie (ERR_TOO_MANY_REDIRECTS)
                // pour tout gérant de boutique non encore affecté à une boutique.
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('login')->with('error', 'Votre compte n\'est pas encore affecté à une boutique. Demandez à un administrateur de vous en attribuer une, puis reconnectez-vous.');
            }
            // Créances et dépenses de SON périmètre : un employé rattaché ne voit
            // pas les chiffres des autres magasins (le solde client, lui, cumule
            // les factures de tous les magasins).
            $total_credits = Facture::whereIn('store_id', $scopeIds)->where('reste', '>', 0)->sum('reste');
            $total_purchases = $this->totalPurchases($store_id);
            $total_sales = Sale::whereIn('store_id', $scopeIds)
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
            $total_expenses = Expense::whereIn('store_id', $scopeIds)->sum('amount');
            $total_customers = Customer::count();
            $total_quantities = Product::resumeStock($scopeIds);
            // Balance = total ventes (toutes dates confondues) - total achats - total
            // dépenses de CETTE boutique — contrairement à $total_sales (ventes du
            // jour) et $total_expenses (tout le périmètre) utilisés par ailleurs
            // sur ce dashboard. $total_purchases est déjà calculé ci-dessus
            // (combine `purchases` + `purchase_orders`, cf. totalPurchases()).
            $total_sales_all_time_store = Facture::where('store_id', $store_id)->sum('montant_total');
            $total_expenses_store = Expense::where('store_id', $store_id)->sum('amount');
            $store_balance = $total_sales_all_time_store - $total_purchases - $total_expenses_store;
            $total_purchase_invoices = Purchase::where('store_id', $store_id)->count()
                + PurchaseOrder::where('store_id', $store_id)->where('status', '!=', 'cancelled')->count();
            $total_sales_invoices = Facture::where('store_id', $store_id)->count();
            $total_third_party_interest = Sale::where('is_third_party', true)->where('store_id', $store_id)->sum('interet');
            $latestSales = Sale::where('store_id', $store_id)->with('product')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
            // whereNull('deleted_at') : sinon une facture supprimée reste comptée
            // dans le chiffre d'affaires mensuel affiché.
            $salesData = DB::table('factures')
                ->whereNull('deleted_at')
                ->where('store_id', $store_id)
                ->whereYear('created_at', Carbon::now()->year)
                ->selectRaw('SUM(montant_total) as total, '.$this->moisSql('created_at').' as month')
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $purchasesData = $this->monthlyPurchases($store_id);
            $gains = Sale::where('store_id', $store_id)->sum('interet');
            $total_gains_all = $gains;
            [$months, $sales, $purchases] = $this->courbesDeLAnnee($salesData, $purchasesData);
        } else {
            $total_credits = Customer::where('balance', '>', 0)->sum('balance');
            $total_purchases = $this->totalPurchases();
            $total_sales = Sale::whereDate('created_at', Carbon::today())
                ->sum('prixTotal');
            $total_sales_paid = Payment::sum('versement');
            $solde_orange_money = Payment::where('paid_by', 'orange money')->sum('versement');
            $solde_cash = Payment::where('paid_by', 'cash')->sum('versement');
            $total_expenses = Expense::sum('amount');
            $total_customers = Customer::count();
            $total_quantities = Product::resumeStock();
            $total_purchase_invoices = Purchase::count() + PurchaseOrder::where('status', '!=', 'cancelled')->count();
            $total_sales_invoices = Facture::count();
            $total_third_party_interest = Sale::where('is_third_party', true)->sum('interet');
            $latestSales = Sale::with('product')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
            // whereNull('deleted_at') : voir plus haut, même raison.
            $salesData = DB::table('factures')
                ->whereNull('deleted_at')
                ->whereYear('created_at', Carbon::now()->year)
                ->selectRaw('SUM(montant_total) as total, '.$this->moisSql('created_at').' as month')
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $purchasesData = $this->monthlyPurchases();
            $gains = Sale::sum('interet');
            $total_gains_all = $gains;
            [$months, $sales, $purchases] = $this->courbesDeLAnnee($salesData, $purchasesData);
        }
        $customers = Customer::all();
        $query = Facture::query();

        if ($request->filled('customer_id')) {
            $query->where('customer_id', 'like', '%'.$request->customer_id.'%');
        }
        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->created_at);
        }

        if ($user->isStoreScoped()) {
            $query->whereIn('store_id', $user->accessibleStoreIds());
        }

        // Les clients sont chargés ici, en une requête : la vue les cherchait
        // un par un avec Customer::find() à l'intérieur de sa boucle.
        $factures = $query->with(['paiements', 'customer'])->get();

        $grouped = $factures->groupBy(function ($facture) {
            return $facture->customer_id.'|'.$facture->created_at->format('Y-m-d');
        });

        // Chiffres par magasin de vente. La vue les calculait dans sa boucle :
        // huit agrégats par magasin, donc un coût qui croissait avec le nombre
        // de boutiques. Ici, un agrégat par mesure, groupé par magasin.
        $magasins = Store::where('type', Store::TYPE_MAGASIN)
            ->orderByDesc('is_main')->orderBy('store_name')->get();
        $idsMagasins = $magasins->pluck('id');

        $parMagasin = [
            'achats' => Purchase::whereIn('store_id', $idsMagasins)
                ->selectRaw('store_id, SUM(price * quantity) as total')->groupBy('store_id')->pluck('total', 'store_id'),
            'commandes' => PurchaseOrder::whereIn('store_id', $idsMagasins)->where('status', '!=', 'cancelled')
                ->selectRaw('store_id, SUM(total_landed_cost_gnf) as total')->groupBy('store_id')->pluck('total', 'store_id'),
            'ventes' => Facture::whereIn('store_id', $idsMagasins)
                ->selectRaw('store_id, SUM(montant_total) as total, SUM(reste) as reste')->groupBy('store_id')->get()->keyBy('store_id'),
            'depenses' => Expense::whereIn('store_id', $idsMagasins)
                ->selectRaw('store_id, SUM(amount) as total')->groupBy('store_id')->pluck('total', 'store_id'),
            'marges' => Sale::whereIn('store_id', $idsMagasins)
                ->selectRaw('store_id, SUM(interet) as total')->groupBy('store_id')->pluck('total', 'store_id'),
            'reglements' => Payment::join('factures', 'factures.id', '=', 'payments.facture_id')
                ->whereIn('factures.store_id', $idsMagasins)
                ->whereNull('factures.deleted_at')
                ->selectRaw('factures.store_id, payments.paid_by, SUM(payments.versement) as total')
                ->groupBy('factures.store_id', 'payments.paid_by')->get()
                ->groupBy('store_id')->map(fn ($lignes) => $lignes->pluck('total', 'paid_by')),
        ];

        // Quantités par unité, pour toutes les factures affichées, en UNE
        // requête. La vue les demandait à l'intérieur de sa boucle : une
        // requête par ligne de tableau, soit la moitié des soixante-huit
        // requêtes que le tableau de bord lançait.
        $quantitesParFacture = Sale::query()
            ->whereIn('numeroFacture', $factures->pluck('numero_facture')->filter()->all())
            ->selectRaw("numeroFacture, COALESCE(unite, 'pcs') as unite, SUM(quantity) as qty")
            ->groupBy('numeroFacture', DB::raw("COALESCE(unite, 'pcs')"))
            ->get()
            ->groupBy('numeroFacture');

        $salesRankingQuery = Sale::query();
        if ($user->isStoreScoped()) {
            $salesRankingQuery->whereIn('store_id', $user->accessibleStoreIds());
        }

        $topProducts = (clone $salesRankingQuery)
            ->whereYear('created_at', now()->year)
            ->select('product_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(prixTotal) as total_revenue'))
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->take(10)
            ->with('product:id,libelle')
            ->get();

        $topProductsByRevenue = (clone $salesRankingQuery)
            ->select('product_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(prixTotal) as total_revenue'))
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->take(10)
            ->with('product:id,libelle')
            ->get();

        // Stock réel sommé depuis store_products, seule source de vérité.
        $stockAlerts = $this->alertesDeStock();

        // Uniquement calculée pour un employé rattaché à un magasin ; la vue admin
        // affiche l'équivalent par boutique via sa propre boucle @foreach(Store::all()).
        $store_balance = $store_balance ?? null;

        // Vue globale multi-emplacements (ventes par période, ventes directes
        // depuis les entrepôts, transferts en cours), limitée au périmètre.
        $overview = $this->locationOverview($user);

        return view('index', compact('overview', 'latestSales', 'total_purchases', 'total_sales', 'total_sales_paid', 'solde_orange_money', 'solde_cash', 'total_expenses', 'total_customers', 'total_quantities', 'total_purchase_invoices', 'total_sales_invoices', 'total_credits', 'gains', 'total_gains_all', 'sales', 'purchases', 'months', 'grouped', 'quantitesParFacture', 'magasins', 'parMagasin', 'customers', 'topProducts', 'topProductsByRevenue', 'stockAlerts', 'total_third_party_interest', 'store_balance'));
    }
}
