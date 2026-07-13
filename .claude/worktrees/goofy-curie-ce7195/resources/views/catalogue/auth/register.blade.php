<!DOCTYPE html>
<html lang="fr">
    @include('layouts.head')
    <body class="login-page">
        <div class="login-container">
            <!-- Left Section - Register Form -->
            <div class="login-form-section">
                <div class="login-form-wrapper">
                    <!-- Logo -->
                    <div class="login-logo">
                        <img src="{{ asset('assets/img/logo.png') }}" alt="EDAAG TRADING Logo" class="logo-image">
                        <h2 class="logo-text">EDAAG TRADING</h2>
                    </div>

                    <!-- Header -->
                    <div class="login-header">
                        <h1 class="login-title">Créer un compte Client</h1>
                        <p class="login-subtitle">Accédez à votre historique de commandes</p>
                    </div>

                    <!-- Flash Messages -->
                    @include('layouts.flash')

                    <form action="{{ route('catalogue.register.submit') }}" method="POST" class="login-form" id="loginForm">
                        @csrf

                        <div class="form-group">
                            <label for="name" class="form-label">
                                <i class="fas fa-user"></i>
                                Nom complet
                            </label>
                            <div class="input-wrapper">
                                <input type="text" id="name" name="name" class="form-input" value="{{ old('name') }}" required>
                                <i class="input-icon fas fa-user"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i>
                                Email
                            </label>
                            <div class="input-wrapper">
                                <input type="email" id="email" name="email" class="form-input" value="{{ old('email') }}" required>
                                <i class="input-icon fas fa-envelope"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone"></i>
                                Téléphone
                            </label>
                            <div class="input-wrapper">
                                <input type="text" id="phone" name="phone" class="form-input" value="{{ old('phone') }}">
                                <i class="input-icon fas fa-phone"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">
                                <i class="fas fa-map-marker-alt"></i>
                                Adresse
                            </label>
                            <div class="input-wrapper">
                                <input type="text" id="address" name="address" class="form-input" value="{{ old('address') }}">
                                <i class="input-icon fas fa-map-marker-alt"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i>
                                Mot de passe
                            </label>
                            <div class="input-wrapper">
                                <input type="password" id="password" name="password" class="form-input" required>
                                <i class="input-icon fas fa-lock"></i>
                                <button type="button" class="password-toggle" id="togglePassword" aria-label="Toggle password visibility">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation" class="form-label">
                                <i class="fas fa-lock"></i>
                                Confirmation
                            </label>
                            <div class="input-wrapper">
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" required>
                                <i class="input-icon fas fa-lock"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn-login" id="submitBtn">
                            <span class="btn-text">Créer le compte</span>
                            <span class="btn-loader" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </form>

                    <!-- Footer Links -->
                    <div class="login-footer">
                        <p>Déjà un compte ? <a href="{{ route('catalogue.login') }}">Se connecter</a></p>
                        <div style="margin-top: 14px;">
                            <a href="{{ route('storefront') }}" class="btn btn-outline-secondary" style="width:100%; padding:12px; border-radius:12px;">
                                Retour au Catalogue
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Section - Decorative -->
            <div class="login-decorative-section">
                <div class="decorative-content">
                    <div class="decorative-shapes">
                        <div class="shape shape-1"></div>
                        <div class="shape shape-2"></div>
                        <div class="shape shape-3"></div>
                    </div>
                    <div class="decorative-text">
                        <h2>Bienvenue sur EDAAG TRADING</h2>
                        <p>Créez votre compte client en quelques secondes</p>
                        <div class="features-list">
                            <div class="feature-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Historique des commandes</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Commande par boutique</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Accès rapide</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">

        <script>
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            }

            const loginForm = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');

            if (loginForm && submitBtn) {
                loginForm.addEventListener('submit', function(e) {
                    const btnText = submitBtn.querySelector('.btn-text');
                    const btnLoader = submitBtn.querySelector('.btn-loader');
                    
                    if (btnText && btnLoader) {
                        btnText.style.display = 'none';
                        btnLoader.style.display = 'flex';
                        submitBtn.disabled = true;
                    }
                });
            }

            document.querySelectorAll('.form-input').forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });

                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });
        </script>

        @include('layouts.scripts')
    </body>
</html>

