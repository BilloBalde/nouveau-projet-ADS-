{{-- Grille de permissions groupées par module, avec tout-cocher global et par module.
     Attend : $permissionModules, $checked (array d'ids, optionnel). --}}
@php $checked = collect(old('permissions', $checked ?? []))->map(fn ($v) => (int) $v)->all(); @endphp

<div class="d-flex justify-content-between align-items-center mb-2">
    <label style="font-weight:700;margin:0;">Actions autorisées pour ce rôle</label>
    <div>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllPerms(true)">Tout cocher</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllPerms(false)">Tout décocher</button>
    </div>
</div>
<p class="text-muted" style="font-size:13px;">
    Un employé ayant ce rôle ne pourra effectuer <strong>que</strong> les actions cochées ci-dessous
    (menus masqués et accès bloqués pour le reste).
</p>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;">
    @foreach($permissionModules as $module => $permissions)
    <fieldset style="border:1.5px solid #f0e6d8;border-radius:12px;padding:12px 14px;background:#fdfbf7;">
        <legend style="font-size:13px;font-weight:700;color:#a8532a;width:auto;padding:0 6px;margin-bottom:6px;float:none;display:flex;align-items:center;gap:8px;">
            <input type="checkbox" class="module-toggle" data-module="{{ Str::slug($module) }}"
                   style="accent-color:#c1682f;" title="Tout le module"
                   onchange="toggleModule(this)">
            {{ $module }}
        </legend>
        @foreach($permissions as $permission)
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;margin-bottom:6px;cursor:pointer;">
            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                   class="perm-checkbox module-{{ Str::slug($module) }}"
                   style="accent-color:#c1682f;width:15px;height:15px;"
                   {{ in_array($permission->id, $checked, true) ? 'checked' : '' }}>
            {{ $permission->name }}
        </label>
        @endforeach
    </fieldset>
    @endforeach
</div>

@error('permissions')<span class="text-danger">{{ $message }}</span>@enderror
@error('permissions.*')<span class="text-danger">{{ $message }}</span>@enderror

<script>
    function toggleAllPerms(state) {
        document.querySelectorAll('.perm-checkbox, .module-toggle').forEach(cb => cb.checked = state);
    }
    function toggleModule(toggle) {
        document.querySelectorAll('.module-' + toggle.dataset.module).forEach(cb => cb.checked = toggle.checked);
    }
    // Synchroniser l'état initial des cases "module entier"
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.module-toggle').forEach(function (toggle) {
            const boxes = document.querySelectorAll('.module-' + toggle.dataset.module);
            toggle.checked = boxes.length && [...boxes].every(cb => cb.checked);
        });
    });
</script>
