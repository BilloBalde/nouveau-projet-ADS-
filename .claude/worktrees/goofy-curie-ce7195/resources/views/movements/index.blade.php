<!DOCTYPE html>
<html lang="en">
@include('layouts.head')
<body>
    <div id="global-loader">
        <div class="whirly-loader"></div>
    </div>

    <div class="main-wrapper">
        @include('layouts.header')
        @include('layouts.sidebar')

        <div class="page-wrapper">
            <div class="content">
                <div class="page-header">
                    <div class="page-title">
                        <h4>Tous les mouvements</h4>
                        <h6>Ventes, achats, dépenses – consolidés</h6>
                    </div>
                    <div class="page-btn">
                        <span class="badge bg-info" style="font-size: 14px;">
                            Total : {{ $paginated->total() }} mouvement(s)
                        </span>
                    </div>
                </div>

                @include('layouts.flash')

                {{-- 🔵 Cartes récapitulatives --}}
                <div class="row">
                    <div class="col-lg-3 col-sm-6">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5>Ventes</h5>
                                <h3>{{ number_format($totalVentes, 0, ',', ' ') }} F</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h5>Achats</h5>
                                <h3>{{ number_format($totalAchats, 0, ',', ' ') }} F</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5>Dépenses</h5>
                                <h3>{{ number_format($totalDepenses, 0, ',', ' ') }} F</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5>Solde</h5>
                                <h3 class="{{ $solde >= 0 ? '' : 'text-warning' }}">
                                    {{ number_format($solde, 0, ',', ' ') }} F
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 🔍 Filtres améliorés --}}
                <div class="card">
                    <div class="card-body">
                        <form method="get" action="{{ route('movements.index') }}" id="filterForm">
                            <div class="row">
                                <div class="col-lg-1 col-md-2">
                                    <div class="form-group">
                                        <label>Type</label>
                                        <select name="type" class="form-control">
                                            <option value="">Tous</option>
                                            <option value="sale" {{ request('type') == 'sale' ? 'selected' : '' }}>Ventes</option>
                                            <option value="purchase" {{ request('type') == 'purchase' ? 'selected' : '' }}>Achats</option>
                                            <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Dépenses</option>
                                        </select>
                                    </div>
                                </div>

                                @if($isAdmin)
                                <div class="col-lg-2 col-md-4">
                                    <div class="form-group">
                                        <label>Magasin</label>
                                        <select name="store_id" class="form-control">
                                            <option value="">Tous les magasins</option>
                                            @foreach($stores as $store)
                                                <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                                    {{ $store->store_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                @endif
                                <div class="col-lg-2 col-md-4">
                                    <div class="form-group">
                                        <label>Date début</label>
                                        <input type="date" name="date_debut" class="form-control" value="{{ request('date_debut') }}">
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <div class="form-group">
                                        <label>Date fin</label>
                                        <input type="date" name="date_fin" class="form-control" value="{{ request('date_fin') }}">
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <div class="form-group">
                                        <label>Référence</label>
                                        <input type="text" name="reference" class="form-control" placeholder="Référence" value="{{ request('reference') }}">
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">Filtrer</button>
                                    <a href="{{ route('movements.index') }}" class="btn btn-secondary">Réinitialiser</a>
                                    <button type="button" onclick="exportPdf()" class="btn btn-danger ms-2">
                                        <i class="fa fa-file-pdf"></i> PDF
                                    </button>
                                </div>
                            </div>
                            {{-- Raccourcis de période --}}
                            <div class="row mt-2">
                                <div class="col-12">
                                    <span class="me-2">Période rapide :</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="setPeriod('today')">Aujourd'hui</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="setPeriod('week')">Cette semaine</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="setPeriod('month')">Ce mois</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="setPeriod('year')">Cette année</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Script pour les périodes rapides et PDF --}}
                <script>
                function setPeriod(period) {
                    let debut = document.querySelector('input[name="date_debut"]');
                    let fin = document.querySelector('input[name="date_fin"]');
                    let today = new Date();
                    let year = today.getFullYear();
                    let month = String(today.getMonth() + 1).padStart(2, '0');
                    let day = String(today.getDate()).padStart(2, '0');

                    if (period === 'today') {
                        debut.value = `${year}-${month}-${day}`;
                        fin.value = `${year}-${month}-${day}`;
                    } else if (period === 'week') {
                        // Premier jour de la semaine (lundi)
                        let first = today.getDate() - today.getDay() + 1;
                        let firstDay = new Date(today.setDate(first));
                        debut.value = firstDay.toISOString().split('T')[0];
                        fin.value = `${year}-${month}-${day}`;
                    } else if (period === 'month') {
                        debut.value = `${year}-${month}-01`;
                        fin.value = `${year}-${month}-${day}`;
                    } else if (period === 'year') {
                        debut.value = `${year}-01-01`;
                        fin.value = `${year}-12-31`;
                    }
                    document.getElementById('filterForm').submit();
                }

                function exportPdf() {
                    let form = document.getElementById('filterForm');
                    let params = new URLSearchParams(new FormData(form)).toString();
                    window.location.href = "{{ route('movements.pdf') }}?" + params;
                }
                </script>

                {{-- 📊 Tableau des mouvements --}}
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Référence</th>
                                        <th>Type</th>
                                        <th>Produit</th>
                                        <th>Qté</th>
                                        <th>Montant</th>
                                        <th>Détails</th>
                                        @if($isAdmin)
                                            <th>Magasin</th>
                                        @endif
                                        
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($paginated as $mvt)
                                    <tr>
                                        <td>
                                            @if(isset($mvt['date']) && $mvt['date'] instanceof \Carbon\Carbon)
                                                {{ $mvt['date']->format('d/m/Y H:i') }}
                                            @elseif(isset($mvt['date']))
                                                {{ $mvt['date'] }}
                                            @else
                                                {{ $mvt['created_at'] ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>{{ $mvt['reference'] }}</td>
                                        <td>
                                            <span class="badge 
                                                @switch($mvt['type_code'])
                                                    @case('sale') bg-success @break
                                                    @case('purchase') bg-warning text-dark @break
                                                    @case('expense') bg-danger @break
                                                    @default bg-secondary
                                                @endswitch
                                            ">
                                                {{ $mvt['type'] }}
                                            </span>
                                        </td>
                                        <td>{{ $mvt['produit_nom'] }}</td>
                                        <td class="text-center">{{ $mvt['produit_qte'] ?? '—' }}</td>
                                        <td class="{{ $mvt['type_code'] == 'sale' ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($mvt['montant'], 0, ',', ' ') }} F
                                        </td>
                                        <td>{{ $mvt['details'] }}</td>
                                        @if($isAdmin)
                                            <td>{{ $mvt['store_name'] }}</td>
                                        @endif
                                        <td class="text-center">
                                            @php
                                                $editRoute = null;
                                                $deleteRoute = null;
                                                switch($mvt['type_code']) {
                                                    case 'sale':
                                                        $editRoute = route('sales.edit', $mvt['id']);
                                                        $deleteRoute = route('sales.destroy', $mvt['id']);
                                                        break;
                                                    case 'purchase':
                                                        $editRoute = route('proformas.edit', $mvt['id']);
                                                        $deleteRoute = route('proformas.destroy', $mvt['id']);
                                                        break;
                                                    case 'expense':
                                                        $editRoute = route('expenses.edit', $mvt['id']);
                                                        $deleteRoute = route('expenses.destroy', $mvt['id']);
                                                        break;
                                                }
                                            @endphp
                                          
                                          
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="{{ $isAdmin ? 9 : 8 }}" class="text-center">
                                            Aucun mouvement trouvé.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                {{-- Pied de tableau avec totaux --}}
                                @if($paginated->count() > 0)
                                <tfoot>
                                    <tr class="font-weight-bold bg-light">
                                        <td colspan="5" class="text-end">TOTAUX :</td>
                                        <td class="{{ $paginated->sum('montant') >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($paginated->sum('montant'), 0, ',', ' ') }} F
                                        </td>
                                        <td colspan="{{ ($isAdmin ? 3 : 2) }}"></td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>

                        {{-- Pagination corrigée --}}
                        <div class="mt-3">
                            @if(method_exists($paginated, 'links'))
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="text-muted small mb-2 mb-sm-0">
                                        @if($paginated->total() > 0)
                                            Affichage de {{ $paginated->firstItem() }} à {{ $paginated->lastItem() }} sur {{ $paginated->total() }} résultats
                                        @endif
                                    </div>
                                    <div>
                                        {{ $paginated->onEachSide(2)->links('pagination::bootstrap-5') }}
                                    </div>
                                </div>
                            @else
                                <div class="d-flex justify-content-center">
                                    {{ $paginated->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('layouts.scripts')
</body>
</html>