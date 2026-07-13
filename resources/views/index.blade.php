@extends('layouts.template')
@section('content')
<div class="content">

    @if(auth()->user()->role_id !== 3)
    @foreach (App\Models\Store::all() as $item)
    <div class="store-section mb-4">
        <div class="store-header d-flex align-items-center mb-3">
            <i class="fas fa-store me-2 text-primary"></i>
            <h3 class="mb-0">{{ $item->store_name }}</h3>
        </div>
        
        @php
        $total_purchases = App\Models\Achat::where('store_id', $item->id)->selectRaw('SUM(grand_total) as total')->value('total');
        $total_sales = App\Models\Facture::where('store_id', $item->id)->sum('montant_total');
        $total_expenses = App\Models\Expense::where('store_id', $item->id)->sum('amount');
        $gains = App\Models\Sale::where('store_id', $item->id)->sum('interet');
        $total_credits_store = App\Models\Facture::where('store_id', $item->id)->sum('reste');
        $total_quantities_store = App\Models\StoreProduct::where('store_id', $item->id)->sum('quantity');
        $solde_orange_money_store = App\Models\Payment::whereHas('facture', fn($q) => $q->where('store_id', $item->id))->where('paid_by', 'orange money')->sum('versement');
        $solde_cash_store = App\Models\Payment::whereHas('facture', fn($q) => $q->where('store_id', $item->id))->where('paid_by', 'cash')->sum('versement');
        @endphp
        
        <div class="store-metrics-grid">
            <!-- Total Achat -->
            <div class="metric-card metric-card-1">
                <div class="dash-count" style="background: linear-gradient(135deg, #92400e, #78350f);">
                    <div class="dash-counts">
                        <h4 class="mb-1">{{ numberDelimiter($total_purchases) }} FG</h4>
                        <h5 class="opacity-75">Total Achat</h5>
                    </div>
                    <div class="dash-imgs">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>

            <!-- Total Ventes -->
            <div class="metric-card metric-card-2">
                <div class="dash-count" style="background: linear-gradient(135deg, #d97706, #b45309);">
                    <div class="dash-counts">
                        <h4 class="mb-1">{{ numberDelimiter($total_sales) }} FG</h4>
                        <h5 class="opacity-75">Total Ventes</h5>
                    </div>
                    <div class="dash-imgs">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>

            <!-- Dettes -->
            <div class="metric-card metric-card-3">
                <div class="dash-count" style="background: linear-gradient(135deg, #c1682f, #9a3412);">
                    <div class="dash-counts">
                        <h4 class="mb-1">{{ numberDelimiter($total_credits_store) }} FG</h4>
                        <h5 class="opacity-75">Dettes (non payé)</h5>
                    </div>
                    <div class="dash-imgs">
                        <i class="fas fa-credit-card"></i>
                    </div>
                </div>
            </div>

            <!-- Stock -->
            <div class="metric-card metric-card-4">
                <div class="dash-count" style="background: linear-gradient(135deg, #78716c, #57534e);">
                    <div class="dash-counts">
                        <h4 class="mb-1">{{ $total_quantities_store }}</h4>
                        <h5 class="opacity-75">Quantité en Stock</h5>
                    </div>
                    <div class="dash-imgs">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>
            </div>

            <!-- Solde Orange Money -->
            <div class="metric-card metric-card-5">
                <div class="dash-count" style="background: linear-gradient(135deg, #ff7900, #e56a00);">
                    <div class="dash-counts">
                        <h4 class="mb-1">{{ numberDelimiter($solde_orange_money_store) }} FG</h4>
                        <h5 class="opacity-75">Solde Orange Money</h5>
                    </div>
                    <div class="dash-imgs">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                </div>
            </div>

            <!-- Solde Cash -->
            <div class="metric-card metric-card-6">
                <div class="dash-count" style="background: linear-gradient(135deg, #059669, #047857);">
                    <div class="dash-counts">
                        <h4 class="mb-1">{{ numberDelimiter($solde_cash_store) }} FG</h4>
                        <h5 class="opacity-75">Solde Cash</h5>
                    </div>
                    <div class="dash-imgs">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
    @else
    <div class="store-section mb-4">
        
        <div class="store-metrics-grid">
            <!-- Total Achat -->
            <div class="metric-card metric-card-1">
                <div class="dash-count" style="background: linear-gradient(135deg, #92400e, #78350f);">
                    <div class="dash-counts">
                        <h4 class="mb-1">{{ numberDelimiter($total_purchases) }} FG</h4>
                        <h5 class="opacity-75">Total Achat</h5>
                    </div>
                    <div class="dash-imgs">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>

            <!-- Total Ventes -->
            <div class="metric-card metric-card-2">
                <div class="dash-count" style="background: linear-gradient(135deg, #d97706, #b45309);">
                    <div class="dash-counts">
                        <h4 class="mb-1">{{ numberDelimiter($total_sales) }} FG</h4>
                        <h5 class="opacity-75">Total Ventes</h5>
                    </div>
                    <div class="dash-imgs">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>

            <!-- Stock -->
            <div class="metric-card metric-card-4">
                <div class="dash-count" style="background: linear-gradient(135deg, #78716c, #57534e);">
                    <div class="dash-counts">
                        <h4 class="mb-1">{{ $total_quantities }}</h4>
                        <h5 class="opacity-75">Quantité en Stock</h5>
                    </div>
                    <div class="dash-imgs">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>
            </div>

            <!-- Solde Orange Money -->
            <div class="metric-card metric-card-5">
                <div class="dash-count" style="background: linear-gradient(135deg, #ff7900, #e56a00);">
                    <div class="dash-counts">
                        <h4 class="mb-1">{{ numberDelimiter($solde_orange_money) }} FG</h4>
                        <h5 class="opacity-75">Solde Orange Money</h5>
                    </div>
                    <div class="dash-imgs">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                </div>
            </div>

            <!-- Solde Cash -->
            <div class="metric-card metric-card-6">
                <div class="dash-count" style="background: linear-gradient(135deg, #059669, #047857);">
                    <div class="dash-counts">
                        <h4 class="mb-1">{{ numberDelimiter($solde_cash) }} FG</h4>
                        <h5 class="opacity-75">Solde Cash</h5>
                    </div>
                    <div class="dash-imgs">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Site Metrics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <a href="{{ route('customers.index') }}" class="dash-count das3 text-decoration-none metric-card quick-action">
                <div class="dash-counts">
                    <h4 class="mb-1">{{ numberDelimiter($total_gains_all) }} FG</h4>
                    <h5 class="opacity-75">Gain total</h5>
                </div>
                <div class="dash-imgs">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </a>
        </div>
        <div class="col-md-3">
        
            <a href="{{ route('reports.daily') }}" class="dash-count das1 text-decoration-none metric-card quick-action">
                <div class="dash-counts">
                    <h4 class="mb-1">Rapport Journalier</h4>
                    <h5 class="opacity-75">(achats/ventes/dépenses)</h5>
                </div>
                <div class="dash-imgs">
                    <i class="fas fa-chart-pie"></i>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('movements.index') }}" class="dash-count das4 text-decoration-none metric-card quick-action">
                <div class="dash-counts">
                    <h4 class="mb-1">Mouvements</h4>
                    <h5 class="opacity-75">(achats/ventes/dépenses)</h5>
                </div>
                <div class="dash-imgs">
                    <i class="fas fa-file-invoice"></i>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('factures.index') }}" class="dash-count das3 text-decoration-none metric-card quick-action">
                <div class="dash-counts">
                    <h4 class="mb-1">Factures</h4>
                    <h5 class="opacity-75">Ventes</h5>
                </div>
                <div class="dash-imgs">
                    <i class="fas fa-file-invoice"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card card-chart">
                <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Ventes & Achats</h5>
                </div>
                <div class="card-body pt-3">
                    <div id="sales_charts"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Report -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Rapport Journalier</h5>
                    <div class="search-set">
                        <div class="search-input">
                            <a class="btn btn-searchset btn-sm" id="filter_search">
                                <i class="fas fa-filter me-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Section -->
                <div class="card-body border-top" id="filter_inputs" style="display: none;">
                    <form action="{{ route('home') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group">
                                    <label class="form-label small text-muted mb-1">Client</label>
                                    <select name="customer_id" id="customer_id" class="form-control form-control-sm">
                                        <option value="">Sélectionner Client</option>
                                        @foreach ($customers as $item)
                                        <option value="{{ $item->id }}" {{ request('customer_id') == $item->id ? 'selected' : '' }}>{{ $item->customerName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group">
                                    <label class="form-label small text-muted mb-1">Date</label>
                                    <input type="date" name="created_at" value="{{ request('created_at') }}" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 d-flex align-items-end">
                                <div class="form-group d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-search me-1"></i> Rechercher
                                    </button>
                                    <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-times me-1"></i> Annuler
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Table -->
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-hover datanew">
                            <thead class="table-light">
                                <tr>
                                    <th>Date Commande</th>
                                    <th>Client</th>
                                    <th class="text-center">Quantité</th>
                                    <th class="text-end">Montant Total</th>
                                    <th class="text-end">Montant Payé</th>
                                    <th class="text-end">Reste à payer</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($grouped as $key => $factures)
                                    @php
                                        [$customerId, $date] = explode('|', $key);
                                        $customer = \App\Models\Customer::find($customerId);
                                        $totalQuantity = $factures->sum('quantity');
                                        $totalMontant = $factures->sum('montant_total');
                                        $totalReste = $factures->sum('reste');
                                        $totalPaye = $factures->sum(function ($facture) {
                                            return optional($facture->paiements()->latest()->first())->total_paye ?? 0;
                                        });
                                    @endphp
                                <tr>
                                    <td class="fw-medium">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium">{{ $customer->customerName ?? 'N/A' }}</span>
                                            @if($customer && $customer->phone)
                                            <small class="text-muted">{{ $customer->phone }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark">{{ $totalQuantity }}</span>
                                    </td>
                                    <td class="text-end fw-medium">{{ number_format($totalMontant, 2) }} FG</td>
                                    <td class="text-end text-success">{{ number_format($totalPaye, 2) }} FG</td>
                                    <td class="text-end">
                                        <span class="badge {{ $totalReste > 0 ? 'bg-warning' : 'bg-success' }}">
                                            {{ number_format($totalReste, 2) }} FG
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Sales -->
    <div class="card">
        <div class="card-header border-0 pb-0">
            <h5 class="card-title mb-0">Ventes Récentes</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead class="table-light">
                        <tr>
                            <th>N° Facture</th>
                            <th>Produit</th>
                            <th class="text-center">Quantité</th>
                            <th class="text-end">Prix Unitaire</th>
                            <th class="text-end">Sous-total</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($latestSales as $item)
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark">{{ $item->numeroFacture }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="product-img me-3">
                                        <img src="{{ asset('products/' . $item->produitImage) }}" alt="{{ $item->produit }}" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                    </div>
                                    <div>
                                        <span class="fw-medium">{{ $item->produit }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">{{ numberDelimiter($item->prix) }} FG</td>
                            <td class="text-end fw-medium text-primary">{{ numberDelimiter($item->prixTotal) }} FG</td>
                            <td>{{ \Carbon\Carbon::parse($item->updated_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Classement des produits -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title mb-0"><i class="fas fa-chart-bar me-1"></i> Top 10 Produits les plus vendus &ndash; cette année</h5>
                </div>
                <div class="card-body">
                    <div id="top_products_chart"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title mb-0"><i class="fas fa-trophy me-1"></i> Classement des produits</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Position</th>
                                    <th>Produit</th>
                                    <th class="text-end">Ventes</th>
                                    <th class="text-end">Chiffre d'affaires</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topProductsByRevenue as $index => $item)
                                <tr>
                                    <td>
                                        @if($index === 0) 🥇 1er
                                        @elseif($index === 1) 🥈 2ème
                                        @elseif($index === 2) 🥉 3ème
                                        @else {{ $index + 1 }}ème
                                        @endif
                                    </td>
                                    <td class="fw-medium">{{ $item->product->libelle ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($item->total_quantity) }} unités</td>
                                    <td class="text-end text-primary fw-medium">{{ numberDelimiter($item->total_revenue) }} FG</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Aucune vente enregistrée.</td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($topProductsByRevenue->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total :</th>
                                    <th class="text-end text-primary">{{ numberDelimiter($topProductsByRevenue->sum('total_revenue')) }} FG</th>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertes stock -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title mb-0"><i class="fas fa-chart-bar me-1"></i> Niveau de stock &ndash; Produits critiques</h5>
                </div>
                <div class="card-body">
                    <div id="stock_alerts_chart"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title mb-0"><i class="fas fa-exclamation-triangle me-1"></i> Alertes stock</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                        <table class="table table-hover">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-end">Quantité en stock</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($stockAlerts as $item)
                                <tr>
                                    <td class="fw-medium">{{ $item->libelle }}</td>
                                    <td class="text-end">{{ $item->pcs }} pcs</td>
                                    <td>
                                        @if($item->pcs <= 0)
                                        <span class="badge bg-danger">Rupture de stock</span>
                                        @else
                                        <span class="badge bg-warning text-dark">Stock faible (&le; 100)</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Aucune alerte de stock pour le moment.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Store Section */
    .store-section {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid #eef2f7;
    }

    .store-header {
        padding-bottom: 15px;
        border-bottom: 2px solid #f1f5f9;
    }

    .store-header h3 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #2c3e50;
    }

    /* Store Metrics Grid */
    .store-metrics-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }

    @media (max-width: 992px) {
        .store-metrics-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .metric-card {
        transition: all 0.3s ease;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #eef2f7;
    }

    .metric-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }

    .metric-card .dash-count {
        border: none;
    }

    /* Metric Icons */
    .metric-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
    }

    .dash-widgetimg .metric-icon {
        margin: 0;
    }

    .dash-widgetcontent h5 {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .dash-widgetcontent h6 {
        font-size: 13px;
        color: #64748b !important;
    }

    .dash-count {
        padding: 20px;
        color: white;
        border-radius: 8px;
    }

    .dash-counts h4 {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .dash-counts h5 {
        font-size: 13px;
        opacity: 0.9;
    }

    .dash-imgs i {
        font-size: 32px;
        opacity: 0.9;
    }

    /* Site Metrics */
    .site-metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .quick-action {
        transition: all 0.3s ease;
        border-radius: 8px;
    }

    .quick-action:hover {
        transform: translateY(-3px);
        text-decoration: none;
    }

    /* Chart Card */
    .card-chart {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .card-chart .card-header {
        background: white;
        border-bottom: 1px solid #eef2f7;
    }

    .legend-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    /* Table Improvements */
    .table-hover tbody tr:hover {
        background-color: #f8fafc;
    }

    .table thead th {
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        color: #64748b;
        border-bottom: 2px solid #eef2f7;
    }

    .table tbody td {
        vertical-align: middle;
        padding: 12px 16px;
        border-color: #eef2f7;
    }

    .badge {
        font-weight: 500;
        padding: 4px 10px;
        font-size: 12px;
    }

    /* Filter Section */
    #filter_inputs {
        border-top: 1px solid #eef2f7;
        background: #f8fafc;
    }

    .btn-searchset {
        background: #3b82f6;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
    }

    .btn-searchset:hover {
        background: #2563eb;
        color: white;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .store-metrics-grid,
        .site-metrics-grid {
            grid-template-columns: 1fr;
        }
        
        .store-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Toggle filter section
        document.getElementById('filter_search').addEventListener('click', function () {
            var filterSection = document.getElementById('filter_inputs');
            if (filterSection.style.display === 'none') {
                filterSection.style.display = 'block';
            } else {
                filterSection.style.display = 'none';
            }
        });

        // Initialize chart
        if (document.getElementById('sales_charts')) {
            var options = {
                series: [
                    {
                        name: 'Ventes',
                        data: @json($sales),
                    },
                    {
                        name: 'Achats',
                        data: @json($purchases),
                    }
                ],
                colors: ['#28C76F', '#EA5455'],
                chart: {
                    type: 'bar',
                    height: 300,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            zoom: false,
                            zoomin: false,
                            zoomout: false,
                            pan: false,
                            reset: false
                        }
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '50%',
                        borderRadius: 6,
                        borderRadiusApplication: 'end',
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                xaxis: {
                    categories: @json($months),
                    labels: {
                        style: {
                            colors: '#64748b',
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function (val) {
                            return val.toLocaleString() + ' FG';
                        },
                        style: {
                            colors: '#64748b',
                            fontSize: '12px'
                        }
                    }
                },
                fill: {
                    opacity: 1
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val.toLocaleString() + ' FG';
                        }
                    }
                },
                grid: {
                    borderColor: '#e2e8f0',
                    strokeDashArray: 4,
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontSize: '14px'
                }
            };

            var chart = new ApexCharts(document.querySelector("#sales_charts"), options);
            chart.render();
        }

        // Top 10 produits les plus vendus (quantité en barres, chiffre d'affaires en courbe)
        if (document.getElementById('top_products_chart')) {
            var topProductsOptions = {
                series: [
                    {
                        name: 'Quantité vendue',
                        type: 'column',
                        data: @json($topProducts->pluck('total_quantity')),
                    },
                    {
                        name: "Chiffre d'affaires (FG)",
                        type: 'line',
                        data: @json($topProducts->pluck('total_revenue')),
                    }
                ],
                chart: {
                    height: 350,
                    type: 'line',
                    toolbar: { show: true }
                },
                colors: ['#93c5fd', '#2dd4bf'],
                stroke: {
                    width: [0, 3]
                },
                plotOptions: {
                    bar: {
                        columnWidth: '55%',
                        borderRadius: 4
                    }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: @json($topProducts->map(fn($item) => $item->product->libelle ?? '—')),
                    labels: {
                        rotate: -45,
                        style: { fontSize: '11px' }
                    }
                },
                yaxis: [
                    {
                        title: { text: 'Quantité vendue' }
                    },
                    {
                        opposite: true,
                        title: { text: "Chiffre d'affaires (FG)" },
                        labels: {
                            formatter: function (val) {
                                return val.toLocaleString();
                            }
                        }
                    }
                ],
                grid: {
                    borderColor: '#e2e8f0',
                    strokeDashArray: 4,
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right'
                }
            };
            var topProductsChart = new ApexCharts(document.querySelector("#top_products_chart"), topProductsOptions);
            topProductsChart.render();
        }

        // Niveau de stock des produits critiques (triés du plus faible au plus élevé)
        if (document.getElementById('stock_alerts_chart')) {
            var stockAlertsOptions = {
                series: [
                    {
                        name: 'Quantité en stock',
                        data: @json($stockAlerts->pluck('pcs')),
                    }
                ],
                chart: {
                    height: 350,
                    type: 'bar',
                    toolbar: { show: true }
                },
                colors: ['#22d3ee'],
                plotOptions: {
                    bar: {
                        columnWidth: '70%',
                        borderRadius: 3
                    }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: @json($stockAlerts->pluck('libelle')),
                    labels: {
                        rotate: -45,
                        style: { fontSize: '10px' }
                    }
                },
                yaxis: {
                    title: { text: "Nombre d'unités" }
                },
                grid: {
                    borderColor: '#e2e8f0',
                    strokeDashArray: 4,
                }
            };
            var stockAlertsChart = new ApexCharts(document.querySelector("#stock_alerts_chart"), stockAlertsOptions);
            stockAlertsChart.render();
        }

        // Add hover effect to metric cards
        const metricCards = document.querySelectorAll('.metric-card');
        metricCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transition = 'all 0.3s ease';
            });
        });

        // Format table numbers
        document.querySelectorAll('.table tbody td').forEach(cell => {
            if (cell.textContent.includes('FG')) {
                cell.classList.add('fw-medium');
            }
        });
    });
</script>
@endsection