@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Comptes des associés — {{ $annee }}</h4>
            <h6>Part du bénéfice − sorties de l'année = ce qui revient à chacun à la clôture</h6>
        </div>
        <div class="page-btn d-flex flex-wrap gap-2">
            <a href="{{ route('associates.annee', $annee) }}" class="btn btn-cancel">
                <i class="fas fa-calculator me-1"></i> Fin d'année : partage
            </a>
            <a href="{{ route('associates.create') }}" class="btn btn-added">
                <i class="fas fa-plus me-1"></i> Nouvel associé
            </a>
        </div>
    </div>

    @include('layouts.flash')

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <a href="{{ route('associates.index', ['annee' => $annee - 1]) }}" class="btn btn-sm btn-light" title="Année précédente"><i class="fas fa-chevron-left"></i></a>
                <strong class="px-1">{{ $annee }}</strong>
                @if ($annee < now()->year)
                <a href="{{ route('associates.index', ['annee' => $annee + 1]) }}" class="btn btn-sm btn-light" title="Année suivante"><i class="fas fa-chevron-right"></i></a>
                @endif
                @if ($cloturee)
                    <span class="badge bg-success ms-2"><i class="fas fa-lock me-1"></i>Année clôturée</span>
                @else
                    <span class="badge bg-light text-dark border ms-2">Année en cours — soldes provisoires</span>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Associé</th>
                            <th>Téléphone</th>
                            <th class="text-end">Report</th>
                            <th class="text-end">Part du bénéfice</th>
                            <th class="text-end">Sorties</th>
                            <th class="text-end">Solde</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lignes as $ligne)
                        @php($c = $ligne->compte)
                        <tr @class(['text-muted' => ! $ligne->associe->active])>
                            <td class="fw-medium">
                                <a href="{{ route('associates.show', ['associate' => $ligne->associe, 'annee' => $annee]) }}">{{ $ligne->associe->name }}</a>
                                @unless ($ligne->associe->active)<small class="d-block">désactivé</small>@endunless
                            </td>
                            <td>{{ $ligne->associe->phone ?: '—' }}</td>
                            <td class="text-end">{{ $c['report'] ? francs($c['report']) : '—' }}</td>
                            <td class="text-end">{{ $c['part'] ? francs($c['part']) : '—' }}</td>
                            <td class="text-end">{{ $c['sorties'] ? '− '.francs($c['sorties']) : '—' }}</td>
                            <td class="text-end fw-bold {{ $c['solde'] < 0 ? 'text-danger' : '' }}">{{ francs($c['solde']) }}</td>
                            <td class="text-end">
                                <a href="{{ route('associates.show', ['associate' => $ligne->associe, 'annee' => $annee]) }}" class="me-2" title="Relevé"><i class="fas fa-file-invoice"></i></a>
                                <a href="{{ route('associates.edit', $ligne->associe) }}" title="Modifier"><img src="{{ asset('assets/img/icons/edit.svg') }}" alt="Modifier"></a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Aucun associé. <a href="{{ route('associates.create') }}">Enregistrer le premier associé</a>.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if ($lignes->isNotEmpty())
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3">Total</td>
                            <td class="text-end">{{ francs($totaux['part']) }}</td>
                            <td class="text-end">− {{ francs($totaux['sorties']) }}</td>
                            <td class="text-end">{{ francs($totaux['solde']) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
            <p class="text-muted small mb-0">
                Un solde négatif signifie que l'associé a pris plus que sa part : il est reporté sur l'année suivante.
            </p>
        </div>
    </div>
</div>
@endsection
