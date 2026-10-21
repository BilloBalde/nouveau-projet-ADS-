{{--
    Rapport des ventes d'une installation qui compte son stock.

    Il totalise les périodes closes sur la plage demandée, toutes boutiques
    confondues par défaut : c'est ce qui permet de lire « mon chiffre de
    janvier » sans additionner les comptages à la main.

    Les montants sont ceux figés à la validation de chaque comptage.
--}}
@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Rapport des ventes — {{ $label }}</h4>
            <h6>Chiffre d'affaires déduit des comptages de stock clos sur la période</h6>
        </div>
    </div>

    @include('layouts.flash')

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.sales') }}" class="row g-2 align-items-end">
                <div class="col-lg-3 col-sm-6">
                    <label class="form-label">Période</label>
                    <select name="period" class="form-control" onchange="this.form.submit()">
                        @foreach (['daily' => 'Jour', 'weekly' => 'Semaine', 'monthly' => 'Mois', 'quarterly' => 'Trimestre', 'semestral' => 'Semestre', 'yearly' => 'Année'] as $cle => $libelle)
                        <option value="{{ $cle }}" {{ $period === $cle ? 'selected' : '' }}>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <label class="form-label">Boutique</label>
                    <select name="store_id" class="form-control" onchange="this.form.submit()">
                        <option value="">Toutes les boutiques</option>
                        @foreach ($stores as $store)
                        <option value="{{ $store->id }}" {{ (string) $storeId === (string) $store->id ? 'selected' : '' }}>
                            {{ $store->store_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <label class="form-label">Mois</label>
                    <input type="month" name="month" class="form-control" value="{{ request('month', $start->format('Y-m')) }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-submit">Afficher</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-sm-6 col-12">
            <div class="card"><div class="card-body">
                <h6 class="text-muted mb-1">Chiffre d'affaires</h6>
                <h3 class="mb-0">{{ numberDelimiter($totalAmount) }} FG</h3>
            </div></div>
        </div>
        <div class="col-lg-3 col-sm-6 col-12">
            <div class="card"><div class="card-body">
                <h6 class="text-muted mb-1">Marge</h6>
                <h3 class="mb-0">{{ numberDelimiter($totalMargin) }} FG</h3>
            </div></div>
        </div>
        <div class="col-lg-3 col-sm-6 col-12">
            <div class="card"><div class="card-body">
                <h6 class="text-muted mb-1">Quantité vendue</h6>
                <h3 class="mb-0">{{ number_format($totalQuantity, 0, ',', ' ') }}</h3>
            </div></div>
        </div>
        <div class="col-lg-3 col-sm-6 col-12">
            <div class="card"><div class="card-body">
                <h6 class="text-muted mb-1">Pertes</h6>
                <h3 class="mb-0">{{ numberDelimiter($totalLoss) }} FG</h3>
                <small class="text-muted">hors chiffre d'affaires</small>
            </div></div>
        </div>
    </div>

    @if ($seances->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">
        Aucun comptage clos sur cette période.<br>
        <small>Le chiffre d'affaires d'une période apparaît une fois son comptage de fin validé.</small>
    </div></div>
    @else

    {{-- Par produit : ce qui s'est vendu sur la plage, toutes boutiques --}}
    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Par produit</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="text-end">Vendu</th>
                            <th class="text-end">Pertes</th>
                            <th class="text-end">Chiffre d'affaires</th>
                            <th class="text-end">Marge</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($produits as $ligne)
                        <tr>
                            <td>
                                {{ $ligne->produit->libelle ?? 'Produit #'.$ligne->product_id }}
                                <small class="text-muted">· {{ \App\Models\Product::motUnite($ligne->unite, 2) }}</small>
                            </td>
                            <td class="text-end">{{ number_format((int) $ligne->quantite, 0, ',', ' ') }}</td>
                            <td class="text-end text-muted">{{ $ligne->pertes ? number_format((int) $ligne->pertes, 0, ',', ' ') : '—' }}</td>
                            <td class="text-end fw-medium">{{ numberDelimiter($ligne->chiffre) }} FG</td>
                            <td class="text-end text-muted">{{ numberDelimiter($ligne->marge) }} FG</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td class="text-end">Total</td>
                            <td class="text-end">{{ number_format($totalQuantity, 0, ',', ' ') }}</td>
                            <td></td>
                            <td class="text-end">{{ numberDelimiter($totalAmount) }} FG</td>
                            <td class="text-end">{{ numberDelimiter($totalMargin) }} FG</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Les comptages qui composent ce total --}}
    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Comptages pris en compte</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Boutique</th>
                            <th>Période</th>
                            <th class="text-end">Chiffre d'affaires</th>
                            <th class="text-end">Marge</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($seances as $seance)
                        <tr>
                            <td>{{ $seance->reference }}</td>
                            <td>{{ $seance->store->store_name }}</td>
                            <td>
                                @if ($seance->estPremiereSeance())
                                    <span class="text-muted">premier comptage</span>
                                @else
                                    du {{ $seance->debutPeriode()->format('d/m/Y') }}
                                    au {{ $seance->counted_on->format('d/m/Y') }}
                                @endif
                            </td>
                            <td class="text-end">{{ numberDelimiter($seance->total_revenue_gnf) }} FG</td>
                            <td class="text-end text-muted">{{ numberDelimiter($seance->total_margin_gnf) }} FG</td>
                            <td class="text-end">
                                <a href="{{ route('inventaires.show', $seance->id) }}" class="btn btn-sm btn-cancel">Détail</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <small class="text-muted">
                Un comptage apparaît ici dès que sa date de clôture tombe dans la période choisie.
                Les montants sont ceux figés à sa validation.
            </small>
        </div>
    </div>
    @endif
</div>
@endsection
