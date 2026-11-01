@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>{{ $associe->name }} — compte {{ $annee }}</h4>
            <h6>{{ $associe->phone ?: 'Sans téléphone' }}@unless ($associe->active) · associé désactivé @endunless</h6>
        </div>
        <div class="page-btn d-print-none">
            <button type="button" class="btn btn-cancel me-2" onclick="window.print()"><i class="fas fa-print me-1"></i> Imprimer</button>
            @permission('expenses.create')
            @if ($associe->active && ! $compte['cloture'] && $annee === now()->year)
            <a href="{{ route('expenses.create', ['associate_id' => $associe->id]) }}" class="btn btn-cancel me-2"><i class="fas fa-hand-holding-usd me-1"></i> Nouvelle sortie</a>
            @endif
            @endpermission
            <a href="{{ route('associates.edit', $associe) }}" class="btn btn-added"><i class="fas fa-pen me-1"></i> Modifier</a>
        </div>
    </div>

    @include('layouts.flash')

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 d-print-none">
        <a href="{{ route('associates.show', ['associate' => $associe, 'annee' => $annee - 1]) }}" class="btn btn-sm btn-light" title="Année précédente"><i class="fas fa-chevron-left"></i></a>
        <strong class="px-1">{{ $annee }}</strong>
        @if ($annee < now()->year)
        <a href="{{ route('associates.show', ['associate' => $associe, 'annee' => $annee + 1]) }}" class="btn btn-sm btn-light" title="Année suivante"><i class="fas fa-chevron-right"></i></a>
        @endif
        @if ($compte['cloture'])
            <span class="badge bg-success ms-2"><i class="fas fa-lock me-1"></i>Clôturé le {{ $compte['cloture']->closed_at->format('d/m/Y') }}</span>
        @else
            <span class="badge bg-light text-dark border ms-2">Provisoire : l'année n'est pas clôturée</span>
        @endif
    </div>

    {{-- Le calcul, ligne par ligne, tel qu'il sera arrêté à la clôture. --}}
    <div class="card">
        <div class="card-body">
            <table class="table table-sm mb-0" style="max-width:560px;">
                <tbody>
                    <tr>
                        <td>Report de l'année précédente</td>
                        <td class="text-end {{ $compte['report'] < 0 ? 'text-danger' : '' }}">{{ francs($compte['report']) }}</td>
                    </tr>
                    <tr>
                        <td>+ Part du bénéfice {{ $annee }}</td>
                        <td class="text-end">{{ francs($compte['part']) }}</td>
                    </tr>
                    <tr>
                        <td>− Sorties de l'année ({{ $sorties->count() }})</td>
                        <td class="text-end">− {{ francs($compte['sorties']) }}</td>
                    </tr>
                    <tr class="fw-bold" style="border-top:2px solid var(--adm-brand-600,#3E4C21);">
                        <td>= Solde</td>
                        <td class="text-end {{ $compte['solde'] < 0 ? 'text-danger' : '' }}">{{ francs($compte['solde']) }}</td>
                    </tr>
                    <tr>
                        <td>{{ $compte['cloture'] ? 'Versé à l\'associé' : 'À lui verser à la clôture' }}</td>
                        <td class="text-end fw-medium text-success">{{ francs($compte['a_verser']) }}</td>
                    </tr>
                    @if ($compte['a_reporter'] < 0)
                    <tr>
                        <td>Reporté sur {{ $annee + 1 }} (il a pris plus que sa part)</td>
                        <td class="text-end fw-medium text-danger">{{ francs($compte['a_reporter']) }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
            @if (! $compte['cloture'] && $compte['part'] == 0)
            <p class="text-muted small mt-2 mb-0">
                La part du bénéfice se saisit à la fin de l'année, sur la page
                <a href="{{ route('associates.annee', $annee) }}">Fin d'année : partage</a>.
            </p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Sorties {{ $annee }}</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Référence</th>
                            <th>Motif</th>
                            <th>Magasin</th>
                            <th class="text-end">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sorties as $sortie)
                        <tr>
                            <td class="text-nowrap">{{ $sortie->created_at->format('d/m/Y') }}</td>
                            <td class="text-muted">{{ $sortie->reference }}</td>
                            <td>{{ $sortie->description }}</td>
                            <td>{{ $sortie->store->store_name ?? '—' }}</td>
                            <td class="text-end text-nowrap">{{ francs($sortie->amount) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Aucune sortie en {{ $annee }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($historique->isNotEmpty())
    <div class="card d-print-none">
        <div class="card-body">
            <h5 class="mb-3">Clôtures précédentes</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Année</th>
                            <th class="text-end">Report</th>
                            <th class="text-end">Part</th>
                            <th class="text-end">Sorties</th>
                            <th class="text-end">Versé</th>
                            <th class="text-end">Reporté</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($historique as $c)
                        <tr>
                            <td><a href="{{ route('associates.show', ['associate' => $associe, 'annee' => $c->year]) }}">{{ $c->year }}</a></td>
                            <td class="text-end">{{ francs($c->carried_in) }}</td>
                            <td class="text-end">{{ francs($c->profit_share) }}</td>
                            <td class="text-end">− {{ francs($c->withdrawals) }}</td>
                            <td class="text-end text-success">{{ francs($c->paid) }}</td>
                            <td class="text-end {{ $c->carried_out < 0 ? 'text-danger' : '' }}">{{ francs($c->carried_out) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
