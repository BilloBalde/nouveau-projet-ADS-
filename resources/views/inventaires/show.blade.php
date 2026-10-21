{{--
    Rapport de chiffre d'affaires d'une période.

    Tous les termes du calcul sont affichés, dans l'ordre de la soustraction,
    pour que le gérant puisse la refaire à la main sur le rapport qu'il a sous
    les yeux. Les chiffres sont ceux figés à la validation : ils ne bougeront
    plus, même si un prix change ensuite.
--}}
@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Chiffre d'affaires — {{ $seance->reference }}</h4>
            <h6>
                {{ $seance->store->full_name }} ·
                @if ($seance->estPremiereSeance())
                    premier comptage : stock de départ constaté le {{ $seance->counted_on->format('d/m/Y') }}
                @else
                    période du {{ $seance->debutPeriode()->format('d/m/Y') }} au {{ $seance->counted_on->format('d/m/Y') }}
                @endif
            </h6>
        </div>
        <div class="page-btn">
            <a href="{{ route('inventaires.rapport-pdf', $seance->id) }}" class="btn btn-added" target="_blank">
                <i class="fas fa-print me-1"></i> Imprimer le rapport
            </a>
        </div>
    </div>

    @include('layouts.flash')

    <div class="row">
        <div class="col-lg-4 col-sm-6 col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Chiffre d'affaires de la période</h6>
                    <h3 class="mb-0">{{ number_format((float) $seance->total_revenue_gnf, 0, ',', ' ') }} GNF</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-6 col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Quantité vendue</h6>
                    <h3 class="mb-0">{{ number_format((int) $seance->total_sold_quantity, 0, ',', ' ') }}</h3>
                    <small class="text-muted">{{ \App\Models\Product::resumeQuantites($seance->lines, 'sold_quantity') }}</small>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-6 col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Pertes déclarées <small>(hors CA)</small></h6>
                    <h3 class="mb-0">{{ number_format((float) $seance->total_loss_gnf, 0, ',', ' ') }} GNF</h3>
                    <small class="text-muted">casse, vol, péremption</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="alert alert-light border">
                <strong>Comment ce chiffre est obtenu, pour chaque produit :</strong><br>
                compté au début + achats + transferts entrants − transferts sortants − pertes + autres mouvements
                − compté à la fin = <strong>quantité vendue</strong>, puis × prix de vente = chiffre d'affaires.
                Les colonnes ci-dessous donnent chaque terme : la soustraction se vérifie à la main, ligne par ligne.
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="text-end">Début</th>
                            <th class="text-end">Achats</th>
                            <th class="text-end">Entrées</th>
                            <th class="text-end">Sorties</th>
                            <th class="text-end">Pertes</th>
                            <th class="text-end">Autres</th>
                            <th class="text-end">Compté</th>
                            <th class="text-end">Vendu</th>
                            <th class="text-end">Prix</th>
                            <th class="text-end">Chiffre d'affaires</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($seance->lines->sortBy(fn ($l) => $l->product?->libelle) as $ligne)
                        <tr @class(['table-danger' => ! $ligne->calculCoherent()])>
                            <td>
                                <strong>{{ $ligne->product->libelle ?? 'Produit #'.$ligne->product_id }}</strong>
                                <small class="text-muted d-block">
                                    {{ \App\Models\Product::motUnite($ligne->unite, 2) }}
                                    @if ($ligne->fund_id) · fonds {{ $ligne->fund->name ?? '#'.$ligne->fund_id }} @endif
                                </small>
                                @if ($ligne->loss_quantity > 0 && $ligne->loss_reason)
                                <small class="text-warning d-block">Perte : {{ $ligne->loss_reason }}</small>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format((int) $ligne->opening_quantity, 0, ',', ' ') }}</td>
                            <td class="text-end">{{ $ligne->purchased_quantity ? '+'.number_format((int) $ligne->purchased_quantity, 0, ',', ' ') : '—' }}</td>
                            <td class="text-end">{{ $ligne->transfer_in_quantity ? '+'.number_format((int) $ligne->transfer_in_quantity, 0, ',', ' ') : '—' }}</td>
                            <td class="text-end">{{ $ligne->transfer_out_quantity ? '−'.number_format((int) $ligne->transfer_out_quantity, 0, ',', ' ') : '—' }}</td>
                            <td class="text-end">{{ $ligne->loss_quantity ? '−'.number_format((int) $ligne->loss_quantity, 0, ',', ' ') : '—' }}</td>
                            <td class="text-end">
                                @if ($ligne->other_quantity)
                                    {{ $ligne->other_quantity > 0 ? '+' : '−' }}{{ number_format(abs((int) $ligne->other_quantity), 0, ',', ' ') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end fw-medium">{{ number_format((int) $ligne->counted_quantity, 0, ',', ' ') }}</td>
                            <td class="text-end fw-medium">{{ number_format((int) $ligne->sold_quantity, 0, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $ligne->unit_price_gnf, 0, ',', ' ') }}</td>
                            <td class="text-end fw-medium">{{ number_format((float) $ligne->revenue_gnf, 0, ',', ' ') }} GNF</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="8" class="text-end">Total</td>
                            <td class="text-end">{{ number_format((int) $seance->total_sold_quantity, 0, ',', ' ') }}</td>
                            <td></td>
                            <td class="text-end">{{ number_format((float) $seance->total_revenue_gnf, 0, ',', ' ') }} GNF</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row mt-3 text-muted">
                <div class="col-lg-6">
                    <small>
                        Validé le {{ $seance->validated_at?->format('d/m/Y à H:i') }}
                        @if ($seance->validator) par {{ $seance->validator->name }} @endif.
                        Les prix sont ceux figés au comptage : ce rapport ne changera plus.
                    </small>
                </div>
                @if ($seance->notes)
                <div class="col-lg-6"><small><strong>Notes :</strong> {{ $seance->notes }}</small></div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
