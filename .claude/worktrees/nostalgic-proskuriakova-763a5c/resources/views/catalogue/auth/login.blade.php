<!DOCTYPE html>
<html lang="fr">
    @include('layouts.head')
    <body class="login-page">
        <div class="login-container">
            <!-- Left Section - Login Form -->
            <div class="login-form-section">
                <div class="login-form-wrapper">
                    <!-- Logo -->
                    <div class="login-logo">
                        <img src="{{ asset('assets/img/logo.png') }}" alt="EDAAG TRADING Logo" class="logo-image">
                        <h2 class="logo-text">EDAAG TRADING</h2>
                    </div>

                    <!-- Header -->
                    <div class="login-header">
                        <h1 class="login-title">Connexion Client</h1>
                        <p class="login-subtitle">Connectez-vous pour voir votre historique</p>
                    </div>

                    <!-- Flash Messages -->
                    @include('layouts.flash')

                    <!-- Login Form -->
                    <form action="{{ route('catalogue.login.submit') }}" method="POST" class="login-form" id="loginForm">
                        @csrf

                        <!-- Email Field -->
                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i>
                                Messagerie électronique
                            </label>
                            <div class="input-wrapper">
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    class="form-input @error('email') is-invalid @enderror" 
                                    placeholder="Entrez votre adresse email"
                                    value="{{ old('email') }}"
                                    required
                                    autocomplete="email"
                                    autofocus
                                >
                                <i class="input-icon fas fa-envelope"></i>
                            </div>
                            @error('email')
                                <span class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <!-- Password Field -->
                        <div class="form-group">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i>
                                Mot de Passe
                            </label>
                            <div class="input-wrapper">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="form-input @error('password') is-invalid @enderror" 
                                    placeholder="Entrez votre mot de passe"
                                    required
                                    autocomplete="current-password"
                                >
                                <i class="input-icon fas fa-lock"></i>
                                <button type="button" class="password-toggle" id="togglePassword" aria-label="Toggle password visibility">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <span class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-login" id="submitBtn">
                            <span class="btn-text">Se Connecter</span>
                            <span class="btn-loader" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </form>

                    <!-- Footer Links -->
                    <div class="login-footer">
                        <p>Vous n'avez pas de compte ? <a href="{{ route('catalogue.register') }}">Créer un compte</a></p>
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
                        <p>Consultez vos commandes et suivez leur statut</p>
                        <div class="features-list">
                            <div class="feature-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Historique des commandes</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Suivi du statut</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Commande par boutique</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">

        <script>
            // Password toggle functionality
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

            // Form submission with loading state
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

            // Input focus effects
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

