@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>{{ $fund->exists ? 'Modifier l\'associé' : 'Nouvel associé' }}</h4>
            <h6>Son argent et ses marchandises seront suivis séparément des fonds propres</h6>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            @include('layouts.flash')
            <form method="POST" action="{{ $fund->exists ? route('associates.update', $fund) : route('associates.store') }}" class="row">
                @csrf
                @if ($fund->exists) @method('PUT') @endif
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Nom de l'associé</label>
                        <input type="text" name="associate_name" value="{{ old('associate_name', $fund->associate_name) }}" class="form-control" required>
                        @error('associate_name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Nom du fonds (affiché dans les listes)</label>
                        <input type="text" name="name" value="{{ old('name', $fund->name) }}" class="form-control" placeholder="Ex. : Fonds Diallo" required>
                        @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Téléphone</label>
                        <input type="text" name="phone" value="{{ old('phone', $fund->phone) }}" class="form-control">
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Part du bénéfice pour le patron (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="owner_share" value="{{ old('owner_share', $fund->owner_share) }}" class="form-control" {{ $locked ? 'readonly' : '' }}>
                        @error('owner_share') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Part du bénéfice pour l'associé (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="associate_share" value="{{ old('associate_share', $fund->associate_share) }}" class="form-control" {{ $locked ? 'readonly' : '' }}>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Compte de connexion de l'associé (lecture seule)</label>
                        <select name="user_id" class="form-control">
                            <option value="">— Aucun —</option>
                            @foreach ($associateUsers as $u)
                                <option value="{{ $u->id }}" {{ (int) old('user_id', $fund->exists ? $associateUsers->firstWhere('fund_id', $fund->id)?->id : 0) === $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Créez d'abord l'utilisateur avec le rôle « Associé » (Personnes → Utilisateurs).</small>
                    </div>
                </div>
                @if ($locked)
                <div class="col-12">
                    <p class="text-muted" style="font-size:12.5px;">La clé de partage est fixe : elle ne peut plus être modifiée une fois l'activité de l'associé commencée.</p>
                </div>
                @endif
                <div class="col-12">
                    <div class="form-group">
                        <label>Notes (accord, conditions…)</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $fund->notes) }}</textarea>
                    </div>
                </div>
                <div class="col-12 mb-3">
                    <input type="hidden" name="active" value="0">
                    <label class="form-check-label"><input type="checkbox" class="form-check-input me-1" name="active" value="1" {{ old('active', $fund->active) ? 'checked' : '' }}> Actif (proposé dans les achats, ventes et transferts)</label>
                </div>
                <div class="col-12">
                    <button class="btn btn-submit me-2">Enregistrer</button>
                    <a href="{{ $fund->exists ? route('associates.show', $fund) : route('associates.index') }}" class="btn btn-cancel">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
