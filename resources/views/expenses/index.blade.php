@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Sorties aux associés</h4>
            <h6>L'argent donné à chaque associé en cours d'année, déduit de sa part du bénéfice à la clôture</h6>
        </div>
        @permission('expenses.create')
        <div class="page-btn">
            <a href="{{ route('expenses.create') }}" class="btn btn-added"><i class="fas fa-plus me-1"></i> Nouvelle sortie</a>
        </div>
        @endpermission
    </div>

    @include('layouts.flash')

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('expenses.index') }}" class="row g-2 mb-3 align-items-end">
                <div class="col-lg-3 col-sm-6">
                    <label class="form-label small text-muted mb-1">Associé</label>
                    <select name="associate_id" class="form-control">
                        <option value="">Tous les associés</option>
                        @foreach ($associes as $a)
                        <option value="{{ $a->id }}" {{ (string) request('associate_id') === (string) $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-sm-6">
                    <label class="form-label small text-muted mb-1">Du</label>
                    <input type="date" name="du" class="form-control" value="{{ request('du') }}">
                </div>
                <div class="col-lg-2 col-sm-6">
                    <label class="form-label small text-muted mb-1">Au</label>
                    <input type="date" name="au" class="form-control" value="{{ request('au') }}">
                </div>
                <div class="col-lg-2 col-sm-6">
                    <label class="form-label small text-muted mb-1">Référence</label>
                    <input type="text" name="reference" class="form-control" value="{{ request('reference') }}" placeholder="DEP…">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-submit">Filtrer</button>
                    @if (request()->hasAny(['associate_id', 'du', 'au', 'reference']))
                    <a href="{{ route('expenses.index') }}" class="btn btn-cancel">Réinitialiser</a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Référence</th>
                            <th>Associé</th>
                            <th>Motif</th>
                            <th>Magasin</th>
                            <th class="text-end">Montant</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($expenses as $expense)
                        <tr>
                            <td class="text-nowrap">{{ $expense->created_at->format('d/m/Y') }}</td>
                            <td class="text-muted">{{ $expense->reference }}</td>
                            <td class="fw-medium">
                                @if ($expense->associate)
                                    @permission('associates.manage')
                                    <a href="{{ route('associates.show', ['associate' => $expense->associate, 'annee' => $expense->created_at->year]) }}">{{ $expense->associate->name }}</a>
                                    @else
                                    {{ $expense->associate->name }}
                                    @endpermission
                                @else
                                    <span class="text-muted">— ancienne dépense —</span>
                                @endif
                            </td>
                            <td>{{ $expense->description }}</td>
                            <td>{{ $expense->store->store_name ?? '—' }}</td>
                            <td class="text-end fw-medium text-nowrap">{{ francs($expense->amount) }}</td>
                            <td class="text-end text-nowrap">
                                @permission('expenses.edit')
                                <a class="me-2" href="{{ route('expenses.edit', $expense->id) }}" title="Modifier"><img src="{{ asset('assets/img/icons/edit.svg') }}" alt="Modifier"></a>
                                @endpermission
                                @permission('expenses.delete')
                                <a href="#" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="{{ $expense->id }}" data-reference="{{ $expense->reference }}" title="Supprimer"><img src="{{ asset('assets/img/icons/delete.svg') }}" alt="Supprimer"></a>
                                @endpermission
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Aucune sortie enregistrée{{ request()->hasAny(['associate_id', 'du', 'au', 'reference']) ? ' pour ces critères' : '' }}.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if ($expenses->isNotEmpty())
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="5">Total ({{ $expenses->count() }} {{ $expenses->count() > 1 ? 'sorties' : 'sortie' }})</td>
                            <td class="text-end text-nowrap">{{ francs($expenses->sum('amount')) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@include('expenses.delete')
@endsection
@section('script_perso')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('deleteModal').addEventListener('show.bs.modal', function (event) {
            const bouton = event.relatedTarget;
            this.querySelector('#deleteForm').action = '{{ url('expenses') }}/' + bouton.dataset.id;
            this.querySelector('#expense-reference').textContent = bouton.dataset.reference;
        });
    });
</script>
@endsection
