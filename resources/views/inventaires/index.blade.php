@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Inventaires et chiffre d'affaires</h4>
            <h6>Chaque comptage clôt une période : ce qui a disparu du stock, achats et transferts déduits, a été vendu</h6>
        </div>
        <div class="page-btn">
            {{-- Marche à suivre à imprimer et à laisser au gérant près de sa caisse. --}}
            <a href="{{ route('inventaires.mode-operatoire') }}" class="btn btn-cancel me-2" target="_blank">
                <i class="fas fa-book me-1"></i> Mode opératoire
            </a>
            @permission('stock.adjust')
            <a href="{{ route('inventaires.create') }}" class="btn btn-added">
                <i class="fas fa-plus me-1"></i> Nouveau comptage
            </a>
            @endpermission
        </div>
    </div>

    @include('layouts.flash')

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('inventaires.index') }}" class="row g-2 mb-3">
                <div class="col-lg-4 col-sm-6">
                    <select name="store_id" class="form-control" onchange="this.form.submit()">
                        <option value="">Toutes les boutiques</option>
                        @foreach ($stores as $store)
                        <option value="{{ $store->id }}" {{ (string) request('store_id') === (string) $store->id ? 'selected' : '' }}>
                            {{ $store->full_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @if (request('store_id'))
                <div class="col-auto">
                    <a href="{{ route('inventaires.index') }}" class="btn btn-cancel">Réinitialiser</a>
                </div>
                @endif
            </form>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Boutique</th>
                            <th>Période couverte</th>
                            <th>Comptage</th>
                            <th class="text-end">Vendu</th>
                            <th class="text-end">Chiffre d'affaires</th>
                            <th class="text-end">Pertes</th>
                            <th>État</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($seances as $seance)
                        <tr>
                            <td class="fw-medium">{{ $seance->reference }}</td>
                            <td>{{ $seance->store->store_name }}</td>
                            <td>
                                @if ($seance->estPremiereSeance())
                                    <span class="text-muted">premier comptage</span>
                                @else
                                    du {{ $seance->debutPeriode()->format('d/m/Y') }}
                                    au {{ $seance->counted_on->format('d/m/Y') }}
                                @endif
                            </td>
                            <td>{{ $seance->counted_on->format('d/m/Y') }}</td>
                            <td class="text-end">{{ $seance->estValidee() ? number_format((int) $seance->total_sold_quantity, 0, ',', ' ') : '—' }}</td>
                            <td class="text-end fw-medium">
                                {{ $seance->estValidee() ? number_format((float) $seance->total_revenue_gnf, 0, ',', ' ').' GNF' : '—' }}
                            </td>
                            <td class="text-end text-muted">
                                {{ $seance->estValidee() && (float) $seance->total_loss_gnf > 0
                                    ? number_format((float) $seance->total_loss_gnf, 0, ',', ' ').' GNF'
                                    : '—' }}
                            </td>
                            <td>
                                @if ($seance->estValidee())
                                <span class="badge bg-success">Validé</span>
                                @else
                                <span class="badge bg-warning text-dark">Comptage en cours</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($seance->estValidee())
                                <a href="{{ route('inventaires.show', $seance->id) }}" class="btn btn-sm btn-cancel">Voir le rapport</a>
                                @else
                                <a href="{{ route('inventaires.feuille', $seance->id) }}" class="btn btn-sm btn-cancel" target="_blank">Feuille</a>
                                <a href="{{ route('inventaires.edit', $seance->id) }}" class="btn btn-sm btn-added">Saisir</a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                Aucun comptage enregistré.
                                @permission('stock.adjust')
                                <a href="{{ route('inventaires.create') }}">Ouvrir le premier</a> : il posera le stock de départ.
                                @endpermission
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $seances->links() }}
        </div>
    </div>
</div>
@endsection
