{{-- Unités de vente du produit. Le stock et les prix de la fiche sont exprimés
     dans l'unité de base (la pièce, ou le rouleau) ; au point de vente, le
     vendeur choisit l'unité et le prix se calcule (carton de 24 = 24 × prix). --}}
@php
    $unitesCochees = old('unites_vente', isset($product) ? $product->codesUnites() : ['pcs']);
@endphp
<div class="form-section" id="unitesVente">
    <div class="form-section-title"><i class="fa fa-boxes"></i> Unités de vente</div>
    <p class="text-muted" style="font-size:13px;margin-bottom:10px;">
        Cochez les unités dans lesquelles ce produit peut être vendu. Le stock et les prix ci-dessous sont comptés
        <strong>à la pièce</strong> (ou au rouleau pour un produit vendu au rouleau).
    </p>
    <div class="d-flex flex-wrap gap-3 mb-3">
        @foreach (\App\Models\Product::UNITES as $code => $unite)
            <label class="form-check-label" style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                <input type="checkbox" class="form-check-input unite-check" name="unites_vente[]" value="{{ $code }}"
                       style="margin:0;" {{ in_array($code, (array) $unitesCochees, true) ? 'checked' : '' }}>
                {{ $unite['libelle'] }}{{ $code === 'douzaine' ? ' (12 pcs)' : '' }}
            </label>
        @endforeach
    </div>
    @error('unites_vente') <div class="text-danger mb-2">{{ $message }}</div> @enderror
    <div class="row">
        <div class="col-lg-4 col-sm-6 col-12" data-unite-champ="carton">
            <div class="form-group">
                <label for="pcs_par_carton">Nombre de pièces par carton</label>
                <input type="number" min="2" step="1" id="pcs_par_carton" name="pcs_par_carton" class="form-control"
                       value="{{ old('pcs_par_carton', $product->pcs_par_carton ?? '') }}" placeholder="Ex. 24">
                @error('pcs_par_carton') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-lg-4 col-sm-6 col-12" data-unite-champ="paquet">
            <div class="form-group">
                <label for="pcs_par_paquet">Nombre de pièces par paquet</label>
                <input type="number" min="2" step="1" id="pcs_par_paquet" name="pcs_par_paquet" class="form-control"
                       value="{{ old('pcs_par_paquet', $product->pcs_par_paquet ?? '') }}" placeholder="Ex. 10">
                @error('pcs_par_paquet') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-lg-4 col-sm-6 col-12" data-unite-champ="rouleau">
            <div class="form-group">
                <label for="dimension_rouleau">Dimension du rouleau</label>
                <input type="text" maxlength="60" id="dimension_rouleau" name="dimension_rouleau" class="form-control"
                       value="{{ old('dimension_rouleau', $product->dimension_rouleau ?? '') }}" placeholder="Ex. 100 m × 1,5 mm²">
                @error('dimension_rouleau') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        const bloc = document.getElementById('unitesVente');
        const cases = bloc.querySelectorAll('.unite-check');
        function rafraichir() {
            const coches = Array.from(cases).filter(c => c.checked).map(c => c.value);
            bloc.querySelectorAll('[data-unite-champ]').forEach(function (champ) {
                const actif = coches.includes(champ.dataset.uniteChamp);
                champ.style.display = actif ? '' : 'none';
                champ.querySelector('input').required = actif;
            });
        }
        cases.forEach(c => c.addEventListener('change', function () {
            // Le stock d'un produit vendu au rouleau est compté en rouleaux :
            // le rouleau ne se combine pas avec les unités en pièces.
            if (this.checked && this.value === 'rouleau') {
                cases.forEach(o => { if (o.value !== 'rouleau') o.checked = false; });
            } else if (this.checked) {
                cases.forEach(o => { if (o.value === 'rouleau') o.checked = false; });
            }
            rafraichir();
        }));
        rafraichir();
    })();
</script>
