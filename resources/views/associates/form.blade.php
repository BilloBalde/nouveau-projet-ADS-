@extends('layouts.template')
@section('content')
@php $edition = $associe->exists; @endphp
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>{{ $edition ? 'Modifier l\'associé' : 'Nouvel associé' }}</h4>
            <h6>Les fonds de la boutique sont communs : l'associé reçoit une part du bénéfice à la fin de l'année</h6>
        </div>
    </div>

    @include('layouts.flash')

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $edition ? route('associates.update', $associe) : route('associates.store') }}">
                @csrf
                @if ($edition)
                    @method('PUT')
                @endif
                <div class="row">
                    <div class="col-lg-6 col-12">
                        <div class="form-group">
                            <label for="name">Nom de l'associé</label>
                            <input type="text" id="name" name="name" class="form-control" required maxlength="255" value="{{ old('name', $associe->name) }}">
                            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-lg-6 col-12">
                        <div class="form-group">
                            <label for="phone">Téléphone</label>
                            <input type="text" id="phone" name="phone" class="form-control" maxlength="40" value="{{ old('phone', $associe->phone) }}">
                            @error('phone') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="2">{{ old('notes', $associe->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <input type="hidden" name="active" value="0">
                        <label class="d-flex align-items-center gap-2" style="cursor:pointer;">
                            <input type="checkbox" name="active" value="1" {{ old('active', $associe->active) ? 'checked' : '' }} style="width:18px;height:18px;">
                            Associé actif
                        </label>
                        <small class="text-muted">Un associé désactivé ne reçoit plus de sorties, mais son compte reste soldé à la clôture.</small>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-submit me-2">Enregistrer</button>
                        <a href="{{ $edition ? route('associates.show', $associe) : route('associates.index') }}" class="btn btn-cancel">Annuler</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
