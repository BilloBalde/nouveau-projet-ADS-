@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Gestion Utilisateur</h4>
            <h6>Ajouter Utilisateur</h6>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @include('layouts.flash')
            <form action="{{ route('enregistrer') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Nom</label>
                            <input type="text" name="name">
                        </div>
                        @error('name')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Nom Utilisateur</label>
                            <input type="text" name="username">
                        </div>
                        @error('username')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <input type="hidden" name="role_id" value="3">
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Mot de Passe</label>
                            <div class="pass-group">
                                <input type="password" class=" pass-input" name="password">
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
                                <input type="password" class=" pass-input" name="password_confirmation">
                                <span class="fas toggle-password fa-eye-slash"></span>
                            </div>
                        </div>
                        @error('password_confirmation')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone">
                        </div>
                        @error('phone')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="text" name="email">
                        </div>
                        @error('email')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" cols="30" rows="10"></textarea>
                        </div>
                        @error('description')
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
