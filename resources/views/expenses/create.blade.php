@extends('layouts.template')
@section('content')
@php $edition = isset($expense); @endphp
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>{{ $edition ? 'Modifier la sortie' : 'Nouvelle sortie' }}</h4>
            <h6>Argent donné à un associé : il sera déduit de sa part du bénéfice à la fin de l'année</h6>
        </div>
    </div>

    @include('layouts.flash')

    @if ($associes->isEmpty())
    <div class="alert alert-warning">
        Aucun associé enregistré : une sortie est toujours donnée à un associé.
        @permission('associates.manage')
        <a href="{{ route('associates.create') }}" class="alert-link">Enregistrer un associé</a>
        @endpermission
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ $edition ? route('expenses.update', $expense->id) : route('expenses.store') }}" method="POST">
                @csrf
                @if ($edition)
                    @method('PUT')
                @endif
                <div class="row">
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label for="associate_id">Associé</label>
                            <select id="associate_id" name="associate_id" class="form-control" required>
                                <option value="">Choisir l'associé…</option>
                                @foreach ($associes as $a)
                                <option value="{{ $a->id }}" {{ (string) old('associate_id', $expense->associate_id ?? request('associate_id')) === (string) $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                                @endforeach
                            </select>
                            @error('associate_id') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label for="amount">Montant donné (FG)</label>
                            <input type="number" min="1" step="1" id="amount" name="amount" class="form-control" required
                                   value="{{ old('amount', $edition ? (float) $expense->amount : '') }}">
                            @error('amount') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    @if (auth()->user()->isRestrictedToStore() && $stores->count() === 1)
                        <input type="hidden" name="store_id" value="{{ $stores->first()->id }}">
                    @else
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label for="store_id">Magasin d'où sort l'argent</label>
                            <select id="store_id" name="store_id" class="form-control" required>
                                <option value="">Choisir le magasin…</option>
                                @foreach ($stores as $store)
                                <option value="{{ $store->id }}" {{ (string) old('store_id', $expense->store_id ?? ($stores->count() === 1 ? $store->id : '')) === (string) $store->id ? 'selected' : '' }}>{{ $store->store_name }}</option>
                                @endforeach
                            </select>
                            @error('store_id') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    @endif
                    <div class="col-12">
                        <div class="form-group">
                            <label for="description">Motif</label>
                            <textarea id="description" name="description" class="form-control" rows="2" required
                                      placeholder="Ex. avance sur sa part, frais de transport…">{{ old('description', $expense->description ?? '') }}</textarea>
                            @error('description') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <input type="hidden" name="reference" value="{{ $edition ? $expense->reference : $ref }}">
                        <p class="text-muted small mb-3">Référence : {{ $edition ? $expense->reference : $ref }}</p>
                        <button type="submit" class="btn btn-submit me-2">{{ $edition ? 'Enregistrer' : 'Enregistrer la sortie' }}</button>
                        <a href="{{ route('expenses.index') }}" class="btn btn-cancel">Annuler</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
