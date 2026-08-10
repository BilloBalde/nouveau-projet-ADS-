@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Gestion des Utilisateurs</h4>
            <h6>Modifier Utilisateur</h6>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if (Session::has('fall'))
            <div class="alert alert-danger">
                {{ Session::get('fall') }}
                @php
                    Session::forget('fall');
                @endphp
            </div>
            @elseif (Session::has('success'))
            <div class="alert alert-success">
                {{ Session::get('success') }}
                @php
                    Session::forget('success');
                @endphp
            </div>
            @endif
            <form action="{{ route('updateUser', $user->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="text" name="id" id="id" value="{{ $user->id }}" hidden>
                <div class="row">
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Nom</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}">
                        </div>
                        @error('name')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Nom Utilisateur</label>
                            <input type="text" name="username" value="{{ old('username', $user->username) }}">
                        </div>
                        @error('username')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}">
                        </div>
                        @error('phone')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="text" name="email" value="{{ old('email', $user->email) }}">
                        </div>
                        @error('email')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    @include('users.partials.permission-picker')
                    <div class="col-lg-4 col-sm-6 col-12" id="store-field" style="display:none;">
                        <div class="form-group">
                            <label>Boutique affectée</label>
                            <select name="store_id" id="store-select" class="form-control">
                                <option value="">— Ne pas modifier —</option>
                                @foreach($stores as $store)
                                <option value="{{ $store->id }}"
                                    {{ (string) old('store_id', $currentStoreId) === (string) $store->id ? 'selected' : '' }}>
                                    {{ $store->store_name }}{{ $store->manager ? ' (actuellement : '.$store->manager->name.')' : '' }}
                                </option>
                                @endforeach
                            </select>
                            <p class="text-muted" style="font-size:12px;margin-top:4px;">
                                Un vendeur ne peut être responsable que d'une seule boutique. Choisir une boutique ici la retire à son responsable actuel, le cas échéant
                            </p>
                        </div>
                        @error('store_id')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Mot de Passe</label>
                            <div class="pass-group">
                                <input type="password" class=" pass-input" name="password" value="{{ $user->motdepasse }}">
                                <span class="fas toggle-password fa-eye-slash"></span>
                            </div>
                        </div>
                        @error('password')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Confirm Mot de Passe</label>
                            <div class="pass-group">
                                <input type="password" class=" pass-input" name="password_confirmation" value="{{ $user->motdepasse }}">
                                <span class="fas toggle-password fa-eye-slash"></span>
                            </div>
                        </div>
                        @error('password_confirmation')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-12">
                        <button type="submit" class="btn btn-submit me-2">Soumettre</button>
                        <a href="{{ route('users.index') }}" class="btn btn-cancel">Annuler</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    (function () {
        const vendeurRoleId = '{{ $roles->firstWhere('slug', 'user')?->id }}';
        const roleSelect = document.getElementById('role-select');
        const storeField = document.getElementById('store-field');

        function toggleStoreField() {
            storeField.style.display = (roleSelect.value === vendeurRoleId) ? '' : 'none';
        }

        roleSelect.addEventListener('change', toggleStoreField);
        toggleStoreField();
    })();
</script>
@endsection
