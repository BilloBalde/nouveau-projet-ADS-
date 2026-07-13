{{-- Sélecteur de rôle + grille de permissions par module.
     Attend : $roles, $permissionModules, $rolePresets, et optionnellement $user + $userPermissions (édition). --}}
@php
    $selectedRole = old('role_id', isset($user) ? $user->role_id : '');
    $checked = collect(old('permissions', $userPermissions ?? []))->map(fn ($v) => (int) $v)->all();
@endphp

<div class="col-lg-4 col-sm-6 col-12">
    <div class="form-group">
        <label>Rôle <span class="text-danger">*</span></label>
        <select name="role_id" id="role-select" class="form-control" required>
            <option value="">— Choisir un rôle —</option>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" {{ (string) $selectedRole === (string) $role->id ? 'selected' : '' }}>
                    {{ $role->nameRole }}
                </option>
            @endforeach
        </select>
    </div>
    @error('role_id')
    <span class="text-danger">{{ $message }}</span>
    @enderror
</div>

<div class="col-12">
    <input type="hidden" name="sync_permissions" value="1">
    <div class="form-group">
        <label style="font-weight:700;">Permissions</label>
        <p class="text-muted" style="font-size:13px;margin-bottom:12px;">
            Choisir un rôle pré-coche ses permissions par défaut — vous pouvez ensuite ajuster individuellement.
        </p>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px;">
            @foreach($permissionModules as $module => $permissions)
            <fieldset style="border:1.5px solid #f0e6d8;border-radius:12px;padding:12px 14px;background:#fdfbf7;">
                <legend style="font-size:13px;font-weight:700;color:#a8532a;width:auto;padding:0 6px;margin-bottom:6px;float:none;">
                    {{ $module }}
                </legend>
                @foreach($permissions as $permission)
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;margin-bottom:6px;cursor:pointer;">
                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                           class="perm-checkbox" style="accent-color:#c1682f;width:15px;height:15px;"
                           {{ in_array($permission->id, $checked, true) ? 'checked' : '' }}>
                    {{ $permission->name }}
                </label>
                @endforeach
            </fieldset>
            @endforeach
        </div>
    </div>
    @error('permissions')
    <span class="text-danger">{{ $message }}</span>
    @enderror
    @error('permissions.*')
    <span class="text-danger">{{ $message }}</span>
    @enderror
</div>

<script>
    (function () {
        const presets = @json($rolePresets);
        const select = document.getElementById('role-select');
        select.addEventListener('change', function () {
            const ids = presets[this.value] || [];
            document.querySelectorAll('.perm-checkbox').forEach(function (cb) {
                cb.checked = ids.includes(parseInt(cb.value, 10));
            });
        });
    })();
</script>
