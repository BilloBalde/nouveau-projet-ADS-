@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Nouveau comptage</h4>
            <h6>Le comptage clôt la période ouverte par le comptage précédent de la même boutique</h6>
        </div>
    </div>

    @include('layouts.flash')

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('inventaires.store') }}" class="row">
                @csrf

                <div class="col-lg-5 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Boutique à compter</label>
                        <select name="store_id" id="choixBoutique" class="form-control" required>
                            <option value="">Choisir</option>
                            @foreach ($stores as $store)
                            <option value="{{ $store->id }}"
                                    data-dernier="{{ $derniers->get($store->id)?->counted_on?->format('d/m/Y') }}"
                                    {{ (string) old('store_id') === (string) $store->id ? 'selected' : '' }}>
                                {{ $store->full_name }} ({{ $store->type_label }})
                            </option>
                            @endforeach
                        </select>
                        @error('store_id') <span class="text-danger">{{ $message }}</span> @enderror
                        <small class="text-muted d-block mt-1" id="infoPeriode"></small>
                    </div>
                </div>

                <div class="col-lg-3 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Date du comptage</label>
                        <input type="date" name="counted_on" class="form-control"
                               value="{{ old('counted_on', now()->toDateString()) }}" required>
                        @error('counted_on') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="col-lg-4 col-12">
                    <div class="form-group">
                        <label>Notes <small class="text-muted">(facultatif)</small></label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes') }}"
                               placeholder="Inventaire de fin de mois…">
                    </div>
                </div>

                <div class="col-12">
                    <div class="alert alert-info">
                        À l'ouverture, une feuille de comptage s'imprime <strong>sans le stock théorique</strong> :
                        la quantité attendue n'y figure pas, pour que le compteur compte au lieu de recopier.
                        Le stock théorique s'affiche ensuite à la saisie, au bureau, pour repérer un écart avant de valider.
                    </div>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-submit">Ouvrir le comptage et imprimer la feuille</button>
                    <a href="{{ route('inventaires.index') }}" class="btn btn-cancel">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script_perso')
<script>
    // Rappelle depuis quand la période court, pour que le gérant sache ce que
    // ce comptage va couvrir avant de l'ouvrir.
    (function () {
        const select = document.getElementById('choixBoutique');
        const info = document.getElementById('infoPeriode');
        if (!select || !info) return;

        function afficher() {
            const dernier = select.selectedOptions[0]?.dataset.dernier;
            info.textContent = !select.value
                ? ''
                : (dernier
                    ? 'Dernier comptage le ' + dernier + ' : la période part du lendemain.'
                    : 'Aucun comptage précédent : ce premier comptage posera le stock de départ, sans déduire de vente.');
        }

        select.addEventListener('change', afficher);
        afficher();
    })();
</script>
@endsection
