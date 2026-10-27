{{--
    Tableau de bord d'une installation qui déduit son chiffre d'affaires des
    comptages de stock.

    Le tableau de bord de la caisse lit la table des ventes, vide par
    construction dans ce mode : il afficherait zéro partout alors que la
    boutique a vendu. Celui-ci lit les séances d'inventaire validées, et leurs
    totaux figés à la validation — rien n'est recalculé, un chiffre d'affaires
    établi ne bouge plus.
--}}
@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Tableau de bord — {{ $annee }}</h4>
            <h6>Le chiffre d'affaires se déduit des comptages de stock : il se met à jour à chaque comptage validé</h6>
        </div>
        @permission('stock.adjust')
        <div class="page-btn">
            <a href="{{ route('inventaires.create') }}" class="btn btn-added">
                <i class="fas fa-plus me-1"></i> Nouveau comptage
            </a>
        </div>
        @endpermission
    </div>

    @include('layouts.flash')

    @if ($comptages_en_cours > 0)
    <div class="alert alert-warning">
        {{ $comptages_en_cours }} comptage(s) en cours de saisie.
        Tant qu'un comptage n'est pas validé, ce qu'il contient n'entre dans aucun chiffre ci-dessous.
        <a href="{{ route('inventaires.index') }}" class="alert-link">Les terminer</a>.
    </div>
    @endif

    @if ($seances->isEmpty())
    <div class="alert alert-info">
        <strong>Aucun comptage validé pour {{ $annee }}.</strong><br>
        Le chiffre d'affaires apparaîtra après le <strong>deuxième</strong> comptage d'une boutique :
        le premier pose le stock de départ, le suivant mesure ce qui est parti entre les deux.
        @permission('stock.adjust')
        <a href="{{ route('inventaires.create') }}" class="alert-link">Ouvrir un comptage</a>.
        @endpermission
    </div>
    @endif

    {{-- Les chiffres de l'année --}}
    <div class="row">
        <div class="col-lg-3 col-sm-6 col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Chiffre d'affaires {{ $annee }}</h6>
                    <h3 class="mb-0">{{ numberDelimiter($ca_annee) }} FG</h3>
                    <small class="text-muted">{{ number_format($quantite_annee, 0, ',', ' ') }} articles vendus</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6 col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Marge {{ $annee }}</h6>
                    <h3 class="mb-0">{{ numberDelimiter($marge_annee) }} FG</h3>
                    <small class="text-muted">
                        @if ($ca_annee > 0)
                            {{ number_format($marge_annee / $ca_annee * 100, 1, ',', ' ') }} % du chiffre d'affaires
                        @else
                            —
                        @endif
                    </small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6 col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Pertes déclarées</h6>
                    <h3 class="mb-0">{{ numberDelimiter($pertes_annee) }} FG</h3>
                    <small class="text-muted">au prix de revient, hors chiffre d'affaires</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6 col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Achats {{ $annee }}</h6>
                    <h3 class="mb-0">{{ numberDelimiter($total_achats) }} FG</h3>
                    <small class="text-muted">dépenses : {{ numberDelimiter($total_depenses) }} FG</small>
                </div>
            </div>
        </div>
    </div>

    {{-- La dernière période close : c'est le chiffre qu'il vient de constater --}}
    @if ($derniere)
    <div class="card">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h5 class="mb-1">Dernière période close — {{ $derniere->store->store_name }}</h5>
                    <p class="text-muted mb-0">
                        @if ($derniere->estPremiereSeance())
                            Premier comptage du {{ $derniere->counted_on->format('d/m/Y') }} : stock de départ posé, aucune vente déduite.
                        @else
                            Du {{ $derniere->debutPeriode()->format('d/m/Y') }}
                            au {{ $derniere->counted_on->format('d/m/Y') }}
                            · {{ $derniere->reference }}
                        @endif
                    </p>
                </div>
                <div class="col-lg-2 text-lg-end">
                    <h4 class="mb-0">{{ numberDelimiter($derniere->total_revenue_gnf) }} FG</h4>
                    <small class="text-muted">chiffre d'affaires</small>
                </div>
                <div class="col-lg-2 text-lg-end">
                    <a href="{{ route('inventaires.show', $derniere->id) }}" class="btn btn-sm btn-cancel">Voir le détail</a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        {{-- Chiffre d'affaires mois par mois --}}
        <div class="col-lg-7 col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Chiffre d'affaires et achats — {{ $annee }}</h5>
                    <div id="graphiqueInventaire"></div>
                    <small class="text-muted">
                        Le chiffre d'affaires d'une période est rattaché au mois où le comptage l'a close.
                    </small>
                </div>
            </div>
        </div>

        {{-- Ce qui se vend le mieux --}}
        <div class="col-lg-5 col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Ce qui se vend le mieux</h5>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-end">Vendu</th>
                                    <th class="text-end">Chiffre d'affaires</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($meilleuresVentes as $ligne)
                                <tr>
                                    <td>{{ $ligne->produit->libelle ?? 'Produit #'.$ligne->product_id }}</td>
                                    <td class="text-end">{{ number_format((int) $ligne->quantite, 0, ',', ' ') }}</td>
                                    <td class="text-end">{{ numberDelimiter($ligne->chiffre) }} FG</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">Aucune vente constatée pour l'instant.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Les derniers comptages --}}
        <div class="col-lg-7 col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Derniers comptages</h5>
                        <a href="{{ route('inventaires.index') }}" class="btn btn-sm btn-cancel">Tout voir</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Boutique</th>
                                    <th>Période close le</th>
                                    <th class="text-end">Chiffre d'affaires</th>
                                    <th class="text-end">Marge</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($seances->take(8) as $seance)
                                <tr>
                                    <td>
                                        <a href="{{ route('inventaires.show', $seance->id) }}">{{ $seance->store->store_name }}</a>
                                        <small class="text-muted d-block">{{ $seance->reference }}</small>
                                    </td>
                                    <td>{{ $seance->counted_on->format('d/m/Y') }}</td>
                                    <td class="text-end">{{ numberDelimiter($seance->total_revenue_gnf) }} FG</td>
                                    <td class="text-end text-muted">{{ numberDelimiter($seance->total_margin_gnf) }} FG</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">Aucun comptage validé.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stock au plus bas --}}
        <div class="col-lg-5 col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Stock au plus bas</h5>
                    <p class="text-muted"><small>Total en boutique : {{ $total_stock }}</small></p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-end">Restant</th>
                                    <th>État</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($stockAlerts->take(10) as $item)
                                <tr>
                                    <td>{{ $item->libelle }}</td>
                                    <td class="text-end">{{ $item->quantiteAvecUnite($item->stock_total) }}</td>
                                    <td>
                                        @if ($item->stock_total <= 0)
                                        <span class="badge bg-danger">Rupture</span>
                                        @else
                                        <span class="badge bg-warning text-dark">Faible</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">Aucune alerte.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script_perso')
<script>
    (function () {
        var cible = document.querySelector('#graphiqueInventaire');
        if (!cible || typeof ApexCharts === 'undefined') return;

        new ApexCharts(cible, {
            chart: { type: 'bar', height: 320, toolbar: { show: false } },
            series: [
                { name: "Chiffre d'affaires", data: @json(array_values($ca_par_mois)) },
                { name: 'Achats', data: @json(array_map(fn ($v) => -$v, array_values($achats_par_mois))) },
            ],
            colors: ['var(--adm-brand-600,#3E4C21)', 'var(--adm-accent,#C7A150)'],
            plotOptions: { bar: { horizontal: false, columnWidth: '55%' } },
            dataLabels: { enabled: false },
            xaxis: { categories: @json($mois) },
            yaxis: {
                labels: {
                    formatter: function (v) {
                        return new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(v);
                    },
                },
            },
            tooltip: {
                y: {
                    formatter: function (v) {
                        return new Intl.NumberFormat('fr-FR').format(Math.abs(Math.round(v))) + ' FG';
                    },
                },
            },
            legend: { position: 'top' },
        }).render();
    })();
</script>
@endsection
