<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body class="account-page">

    <div class="main-wrapper">
        <div class="account-content">
            <div class="login-wrapper">
                <div class="login-content">
                    <div class="login-userset">
                        <div class="login-logo">
                            <img src="{{ asset('assets/img/logo.png') }}" alt="img">
                        </div>
                        <div class="login-userheading">
                            <h3>Oublie Mot de Passe</h3>
                            <h4>Entrer votre email pour récupérer votre mot de passe</h4>
                            @include('layouts.flash')
                        </div>
                        <form action="{{ route('password.email') }}" method="POST">
                            @csrf
                            <div class="form-login">
                                <label>Email</label>
                                <div class="form-addons">
                                    <input name="email" type="text" placeholder="Enter your email address">
                                    <img src="assets/img/icons/mail.svg" alt="img">
                                </div>
                                @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="form-login">
                                <div class="alreadyuser">
                                    <h4><a href="{{ route('login') }}" class="hover-a">Retour vers la page de connection</a></h4>
                                </div>
                            </div>
                            <div class="form-login">
                                <button class="btn btn-login" type="submit">Envoyer</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="login-img">
                    <img src="assets/img/login.jpg" alt="img">
                </div>
            </div>
        </div>
    </div>
    @include('layouts.scripts')
</body>
</html>
