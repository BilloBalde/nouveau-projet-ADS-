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
                            <input type="text" name="name" value="{{ $user->name }}">
                        </div>
                        @error('name')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Nom Utilisateur</label>
                            <input type="text" name="username" value="{{ $user->username }}">
                        </div>
                        @error('username')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="text" name="phone" value="{{ $user->phone }}">
                        </div>
                        @error('phone')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="text" name="email" value="{{ $user->email }}">
                        </div>
                        @error('email')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <input type="hidden" name="role_id" value="
                    @if(Auth::user()!==null)
                    {{ (Auth::user()->role_id == 1 ) ? 2 : 3 }}
                    @endif
                    ">
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
@endsection
