{{-- Nature de l'emplacement. Aucun magasin ni entrepôt n'est prévu dans le
     code : on en crée autant que nécessaire, ici. --}}
@php $current = $store ?? null; @endphp
<div class="col-lg-4 col-sm-6 col-12">
    <div class="form-group">
        <label><strong>Type d'emplacement</strong></label>
        <select name="type" class="form-control">
            @foreach (\App\Models\Store::TYPES as $value => $label)
                <option value="{{ $value }}" {{ old('type', $current->type ?? 'magasin') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-muted">Magasin = point de vente ; Entrepôt = stockage ; Stock interne = réserve d'un magasin (A1, A2…).</small>
    </div>
    @error('type') <span class="text-danger">{{ $message }}</span> @enderror
</div>
<div class="col-lg-4 col-sm-6 col-12">
    <div class="form-group">
        <label><strong>Rattaché à</strong></label>
        <select name="parent_id" class="form-control">
            <option value="">— Aucun (emplacement autonome) —</option>
            @foreach ($parents as $parent)
                <option value="{{ $parent->id }}" {{ (string) old('parent_id', $current->parent_id ?? '') === (string) $parent->id ? 'selected' : '' }}>
                    {{ $parent->full_name }} ({{ $parent->type_label }})
                </option>
            @endforeach
        </select>
        <small class="text-muted">Ex. : Entrepôt 3 → Magasin principal ; Stock A1 → Magasin Madina. Les vendeurs d'un magasin accèdent aussi à ce qui lui est rattaché.</small>
    </div>
    @error('parent_id') <span class="text-danger">{{ $message }}</span> @enderror
</div>
<div class="col-lg-4 col-sm-6 col-12">
    <div class="form-group">
        <label><strong>Magasin principal / siège</strong></label>
        <div class="form-check mt-2">
            <input type="hidden" name="is_main" value="0">
            <input class="form-check-input" type="checkbox" name="is_main" value="1" id="is_main" {{ old('is_main', $current->is_main ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_main">Désigner comme magasin principal (un seul à la fois)</label>
        </div>
    </div>
</div>
<div class="col-lg-6 col-sm-6 col-12">
    <div class="form-group">
        <label><strong>Adresse</strong></label>
        <input type="text" name="address" class="form-control" value="{{ old('address', $current->address ?? '') }}">
    </div>
</div>
<div class="col-lg-6 col-sm-6 col-12">
    <div class="form-group">
        <label><strong>Téléphone</strong></label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $current->phone ?? '') }}">
    </div>
</div>
