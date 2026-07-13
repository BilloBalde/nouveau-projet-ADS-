@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Profile</h4>
            <h6>Profile de l'Utilisateur</h6>
        </div>
        @include('layouts.flash')
    </div>

    <div class="card">
        <div class="card-body">
            <div class="profile-set">
                <form action="{{ route('profileImage') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="profile-head">
                    </div>
                    <div class="profile-top">
                        @csrf
                        <div class="profile-content">
                            <div class="profile-contentimg">
                                <img src="{{ asset('avatars/'.$connectedUser->profilePic) }}" alt="img" id="blah">
                                <div class="profileupload">
                                    <input type="file" id="imgInp" name="profilePic">
                                    <a href="javascript:void(0);"><img src="assets/img/icons/edit-set.svg" alt="img"></a>
                                    @error('profilePic')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="profile-contentname">
                                <h2>{{ $connectedUser->name }}</h2>
                                <h4>Mettre à Jour votre Photo et Informations.</h4>
                            </div>
                        </div>
                        <br>
                        <div class="ms-auto">
                            <button type="submit" class="btn btn-submit me-2">Valider</button>
                            <button type="reset" class="btn btn-cancel">Annuler</button>
                        </div>
                    </div>
                </form>
            </div>
            <form action="{{ route('profileInfo') }}" method="post">
                <div class="row">
                    @csrf
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Nom</label>
                            <input type="text" name="name" value="{{ $connectedUser->name }}">
                        </div>
                        @error('name')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Nom d'utilisateur</label>
                            <input type="text" name="username" value="{{ $connectedUser->username }}">
                        </div>
                        @error('username')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="text" name="phone" value="{{ $connectedUser->phone }}">
                        </div>
                        @error('phone')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-12">
                        <button type="submit" class="btn btn-submit me-2">Valider</button>
                        <button type="reset" class="btn btn-cancel">Annuler</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer">
            <form action="{{ route('passwordupdate') }}" method="post">
                <div class="row">
                    @csrf
                    <div class="col-lg-6 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="text" name="emailupdate">
                        </div>
                        @error('emailupdate')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-6 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Ancien Mot de Passe</label>
                            <div class="pass-group">
                                <input type="password" class=" pass-input" name="oldpassword">
                                <span class="fas toggle-password fa-eye-slash"></span>
                            </div>
                        </div>
                        @error('oldpassword')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-6 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Nouveau Mot de Passe</label>
                            <div class="pass-group">
                                <input type="password" class=" pass-input" name="password">
                                <span class="fas toggle-password fa-eye-slash"></span>
                            </div>
                        </div>
                        @error('password')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-6 col-sm-6 col-12">
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
                </div>
                <div class="col-lg-12">
                    <button type="submit" class="btn btn-submit me-2">Valider</button>
                    <button type="reset" class="btn btn-cancel">Annuler</button>
                </div>
            </form>
        </div>
        @if (auth()->user()->role_id != 3)
        <div class="card-footer">
            <form action="{{ route('companyCreate') }}" method="post" enctype="multipart/form-data">
                <div class="row">
                    @csrf
                    <input type="hidden" name="id" value="{{ $compagnie->id }}">
                    <div class="col-lg-4 col-sm-4 col-12">
                        <div class="form-group">
                            <label>Nom Compagnie</label>
                            <input type="text" name="name" class="form-control" value="{{ $compagnie->name }}">
                        </div>
                        @error('name')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 col-sm-4 col-12">
                        <div class="form-group">
                            <label>Compagnie Adresse</label>
                            <input type="text" name="address" class="form-control" value="{{ $compagnie->address }}">
                        </div>
                        @error('address')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-4 col-sm-4 col-12">
                        <div class="form-group">
                            <label>Logo Compagnie</label>
                            <input type="file" name="logo" class="form-control">
                        </div>
                        @error('logo')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label>Note</label>
                            <textarea name="about" cols="30" rows="10" class="form-control">{{ $compagnie->about }}</textarea>
                        </div>
                        @error('about')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-lg-12">
                    <button type="submit" class="btn btn-submit me-2">Submit</button>
                    <button type="reset" class="btn btn-cancel">Cancel</button>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
