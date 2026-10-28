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

    {{-- ─────────────────────────────────────────────────────────────
         Le chiffre de l'année, en grand, et ce qui l'explique à côté.
         Un tableau de bord mène avec UN nombre : celui que le gérant est
         venu chercher. Les quatre autres l'éclairent, ils ne le concurrencent
         pas — d'où la différence de taille, assumée.
         ───────────────────────────────────────────────────────────── --}}
    <div class="tdb-entete">
        <div class="tdb-heros">
            <span class="tdb-heros-libelle">Chiffre d'affaires {{ $annee }}</span>
            <span class="tdb-heros-valeur">{{ francs($ca_annee, false) }}<span class="tdb-heros-unite">FG</span></span>
            <span class="tdb-heros-detail">
                {{ number_format($quantite_annee, 0, ',', ' ') }} article{{ $quantite_annee > 1 ? 's' : '' }} vendu{{ $quantite_annee > 1 ? 's' : '' }}
                @if ($mois_en_cours_libelle && $ca_mois_en_cours > 0)
                    · {{ francs($ca_mois_en_cours) }} en {{ $mois_en_cours_libelle }}
                @endif
            </span>
        </div>

        <div class="tdb-chiffres">
            <div class="tdb-chiffre">
                <span class="tdb-chiffre-libelle">Marge</span>
                <span class="tdb-chiffre-valeur">{{ francs($marge_annee) }}</span>
                <span class="tdb-chiffre-detail">
                    @if ($ca_annee > 0)
                        {{ number_format($marge_annee / $ca_annee * 100, 1, ',', ' ') }} % du chiffre d'affaires
                    @else
                        —
                    @endif
                </span>
            </div>
            <div class="tdb-chiffre">
                <span class="tdb-chiffre-libelle">Achats</span>
                <span class="tdb-chiffre-valeur">{{ francs($total_achats) }}</span>
                <span class="tdb-chiffre-detail">dépenses : {{ francs($total_depenses) }}</span>
            </div>
            <div class="tdb-chiffre">
                <span class="tdb-chiffre-libelle">Pertes déclarées</span>
                <span class="tdb-chiffre-valeur">{{ francs($pertes_annee) }}</span>
                <span class="tdb-chiffre-detail">au prix de revient, hors chiffre d'affaires</span>
            </div>
            <div class="tdb-chiffre">
                <span class="tdb-chiffre-libelle">Comptages validés</span>
                <span class="tdb-chiffre-valeur">{{ $seances->count() }}</span>
                <span class="tdb-chiffre-detail">
                    @if ($derniere)
                        dernier le {{ $derniere->counted_on->format('d/m/Y') }}
                    @else
                        aucun pour l'instant
                    @endif
                </span>
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
                    <h4 class="mb-0">{{ francs($derniere->total_revenue_gnf) }}</h4>
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
            <div class="card h-100">
                <div class="card-body">
                    <div class="tdb-carte-titre">
                        <h5>Chiffre d'affaires et achats — {{ $annee }}</h5>
                        <span class="tdb-chiffre-detail">en francs guinéens</span>
                    </div>
                    @if (array_sum($ca_par_mois) > 0 || array_sum($achats_par_mois) > 0)
                    <div id="graphiqueInventaire"></div>
                    <small class="tdb-note">
                        Le chiffre d'affaires d'une période est rattaché au mois où le comptage l'a close.
                        Le détail par période figure dans « Derniers comptages ».
                    </small>
                    @else
                    <div class="tdb-vide">
                        <i class="fas fa-chart-area"></i>
                        <span>Rien à tracer pour {{ $annee }}.</span>
                        <small>La courbe se dessine dès le deuxième comptage validé.</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Ce qui se vend le mieux --}}
        <div class="col-lg-5 col-12">
            <div class="card h-100">
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
                                    <td class="text-end">{{ francs($ligne->chiffre) }}</td>
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
                                    <td class="text-end">{{ francs($seance->total_revenue_gnf) }}</td>
                                    <td class="text-end text-muted">{{ francs($seance->total_margin_gnf) }}</td>
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
    {{-- Les scripts du gabarit sont inclus APRÈS cette section : à l'exécution
         immédiate, ApexCharts n'existe pas encore et le graphique ne se
         dessinait jamais. D'où l'attente du DOM complet. --}}
    document.addEventListener('DOMContentLoaded', function () {
        var cible = document.querySelector('#graphiqueInventaire');
        if (!cible || typeof ApexCharts === 'undefined') return;

        {{-- Les couleurs sont écrites en toutes lettres : ApexCharts ne résout
             pas les variables CSS, il les passerait telles quelles au SVG, qui
             rendrait les tracés transparents. --}}
        var marque = '{{ marqueCouleur('600') }}';
        var sourd  = '{{ marqueEncre('ink-faint') }}';
        var encre  = '{{ marqueEncre('ink-muted') }}';
        var filet  = '{{ marqueEncre('hairline') }}';

        {{-- Mois par mois, c'est une évolution : une courbe, pas des barres.
             Le chiffre d'affaires est la mesure qu'on vient lire — il porte la
             couleur de la marque et une aire dégradée ; les achats restent en
             gris sourd, en pointillé, pour l'éclairer sans le concurrencer.
             Les deux sont en francs : un seul axe, jamais deux échelles. --}}
        var abrege = function (v) {
            var a = Math.abs(v);
            var court = function (n, unite) {
                return n.toFixed(1).replace(/\.0$/, '').replace('.', ',') + ' ' + unite;
            };

            if (a >= 1e9) return court(v / 1e9, 'Md');
            if (a >= 1e6) return court(v / 1e6, 'M');
            if (a >= 1e3) return Math.round(v / 1e3) + ' k';

            return String(Math.round(v));
        };

        new ApexCharts(cible, {
            chart: {
                height: 320,
                toolbar: { show: false },
                fontFamily: "'Source Sans 3', 'Segoe UI', Arial, sans-serif",
                animations: { speed: 500 },
            },
            series: [
                { name: "Chiffre d'affaires", type: 'area', data: @json(array_values($ca_par_mois)) },
                { name: 'Achats', type: 'line', data: @json(array_values($achats_par_mois)) },
            ],
            colors: [marque, sourd],
            stroke: { curve: 'straight', width: [2.5, 2], dashArray: [0, 5] },
            fill: {
                type: ['gradient', 'solid'],
                gradient: { shadeIntensity: 0, opacityFrom: 0.26, opacityTo: 0.02, stops: [0, 95] },
            },
            dataLabels: { enabled: false },
            markers: { size: 0, strokeWidth: 2, strokeColors: '#fff', hover: { size: 5 } },
            grid: {
                borderColor: filet,
                strokeDashArray: 4,
                xaxis: { lines: { show: false } },
                padding: { left: 2, right: 10, top: -6 },
            },
            xaxis: {
                categories: @json($mois),
                axisBorder: { show: false },
                axisTicks: { show: false },
                tooltip: { enabled: false },
                crosshairs: { stroke: { color: filet, width: 1, dashArray: 0 } },
                labels: { style: { colors: sourd, fontSize: '12px' } },
            },
            yaxis: {
                tickAmount: 5,
                labels: { formatter: abrege, style: { colors: sourd, fontSize: '12px' } },
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
                offsetX: -8,
                fontSize: '13px',
                labels: { colors: encre },
                markers: { width: 10, height: 10, radius: 3 },
                itemMargin: { horizontal: 10 },
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (v) {
                        return new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' FG';
                    },
                },
            },
        }).render();
    });
</script>
@endsection
