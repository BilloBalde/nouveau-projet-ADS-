{{-- Affectation à UN magasin, pour les rôles limités à un magasin (ceux dont
     le preset n'inclut pas « stores.all » : vendeur, magasinier, responsable).
     « Le magasin existe-t-il déjà ? » → choisir dans la liste, ou le créer ici :
     l'employé y est alors affecté automatiquement. --}}
@php
    $mode = old('store_mode', 'existing');
    $selectedStore = old('store_id', $currentStoreId ?? '');
@endphp
<div class="col-12" id="store-field" style="display:none;">
    <div class="card" style="background:var(--adm-paper-warm,#F9F9F3);border:1px solid #E2E3CF;">
        <div class="card-body">
            <label class="fw-semibold mb-2">Magasin d'affectation — le magasin existe-t-il déjà ?</label>
            <div class="d-flex flex-wrap gap-3 mb-3">
                <label class="form-check-label"><input type="radio" class="form-check-input me-1" name="store_mode" value="existing" {{ $mode === 'existing' ? 'checked' : '' }}> Choisir un magasin existant</label>
                <label class="form-check-label"><input type="radio" class="form-check-input me-1" name="store_mode" value="new" {{ $mode === 'new' ? 'checked' : '' }}> + Créer un nouveau magasin</label>
            </div>

            <div data-store-mode="existing">
                <select name="store_id" class="form-control">
                    <option value="">{{ isset($currentStoreId) ? '— Aucun magasin (désaffecter) —' : '— Aucun (à affecter plus tard) —' }}</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ (string) $selectedStore === (string) $store->id ? 'selected' : '' }}>
                            {{ $store->full_name }} ({{ $store->type_label }}){{ $store->users_count ? ' — '.$store->users_count.' employé(s)' : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="text-muted mb-0" style="font-size:12px;margin-top:4px;">
                    Un employé n'est affecté qu'à un seul magasin à la fois ; un magasin peut avoir autant de vendeurs que nécessaire.
                    Changer de magasin clôt l'affectation précédente (historique conservé).
                </p>
                @error('store_id') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div data-store-mode="new" class="row">
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Nom du magasin</label>
                        <input type="text" name="new_store[store_name]" value="{{ old('new_store.store_name') }}" class="form-control">
                        @error('new_store.store_name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Localité</label>
                        <select name="new_store[place_id]" class="form-control">
                            <option value="">Sélectionner</option>
                            @foreach($places as $place)
                                <option value="{{ $place->id }}" {{ (string) old('new_store.place_id') === (string) $place->id ? 'selected' : '' }}>{{ $place->placeName }}</option>
                            @endforeach
                        </select>
                        @error('new_store.place_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Adresse</label>
                        <input type="text" name="new_store[address]" value="{{ old('new_store.address') }}" class="form-control">
                    </div>
                </div>
            </div>

            @if (!empty($assignmentHistory) && $assignmentHistory->isNotEmpty())
                <div class="mt-3">
                    <label class="fw-semibold" style="font-size:13px;">Historique des affectations</label>
                    <ul class="mb-0" style="font-size:12px;">
                        @foreach ($assignmentHistory as $a)
                            <li>
                                {{ $a->store?->store_name ?? 'Emplacement supprimé' }} :
                                du {{ $a->started_at?->format('d/m/Y H:i') ?? '?' }}
                                {{ $a->ended_at ? 'au '.$a->ended_at->format('d/m/Y H:i') : '(en cours)' }}
                                {{ $a->assignedBy ? '— par '.$a->assignedBy->name : '' }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
<script>
    (function () {
        const scopedRoleIds = @json(array_map('strval', $scopedRoleIds));
        const roleSelect = document.getElementById('role-select');
        const storeField = document.getElementById('store-field');

        function toggleStoreField() {
            storeField.style.display = roleSelect && scopedRoleIds.includes(String(roleSelect.value)) ? '' : 'none';
        }

        function toggleMode() {
            const mode = storeField.querySelector('input[name="store_mode"]:checked')?.value || 'existing';
            storeField.querySelectorAll('[data-store-mode]').forEach(function (el) {
                el.style.display = el.dataset.storeMode === mode ? '' : 'none';
            });
        }

        roleSelect?.addEventListener('change', toggleStoreField);
        storeField.querySelectorAll('input[name="store_mode"]').forEach(r => r.addEventListener('change', toggleMode));
        toggleStoreField();
        toggleMode();
    })();
</script>
