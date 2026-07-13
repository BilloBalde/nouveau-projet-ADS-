<!DOCTYPE html>
<html lang="fr">
@include('layouts.head')
<head>
    <style>
        /* Reset Password Specific Styles */
        :root {
            --primary-color: #c1682f;
            --secondary-color: #8a5a34;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --dark-bg: linear-gradient(135deg, #c1682f 0%, #8a5a34 100%);
        }

        body {
            background: var(--dark-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .main-wrapper {
            width: 100%;
        }

        .login-wrapper {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 1200px;
            margin: 20px auto;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-content {
            padding: 50px 40px;
            background: white;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo img {
            max-height: 70px;
            transition: transform 0.3s ease;
        }

        .login-logo img:hover {
            transform: scale(1.05);
        }

        .login-userheading {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-userheading h4 {
            font-size: 18px;
            color: #666;
            font-weight: 400;
            line-height: 1.6;
            max-width: 350px;
            margin: 0 auto;
        }

        /* Security Badge */
        .security-badge {
            background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
            border-radius: 50px;
            padding: 8px 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            border: 1px solid #667eea30;
            color: var(--primary-color);
            font-size: 14px;
            font-weight: 500;
        }

        .security-badge i {
            font-size: 16px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            height: 50px;
            padding: 0 45px 0 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .form-control[readonly] {
            background: #f1f3f5;
            border-color: #dee2e6;
            color: #495057;
            cursor: not-allowed;
        }

        /* Password Input Group */
        .pass-group {
            position: relative;
        }

        .pass-input {
            width: 100%;
            height: 50px;
            padding: 0 45px 0 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .pass-input:focus {
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            transition: color 0.3s ease;
            z-index: 10;
        }

        .toggle-password:hover {
            color: var(--primary-color);
        }

        /* Password Strength Meter */
        .password-strength {
            margin-top: 10px;
            height: 4px;
            border-radius: 2px;
            background: #e0e0e0;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        .strength-weak {
            background: var(--danger-color);
            width: 25%;
        }

        .strength-medium {
            background: var(--warning-color);
            width: 50%;
        }

        .strength-strong {
            background: var(--success-color);
            width: 100%;
        }

        .password-requirements {
            margin-top: 10px;
            font-size: 13px;
            color: #666;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #999;
        }

        .requirement.valid {
            color: var(--success-color);
        }

        .requirement i {
            font-size: 12px;
        }

        /* Button Styles */
        .btn-login {
            width: 100%;
            height: 50px;
            background: var(--dark-bg);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-login:hover::before {
            width: 300px;
            height: 300px;
        }

        /* Form Addons (for email input) */
        .form-addons {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-addons input {
            width: 100%;
            height: 50px;
            padding: 0 45px 0 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-addons img {
            position: absolute;
            right: 15px;
            width: 20px;
            height: 20px;
            opacity: 0.5;
        }

        /* Error Messages */
        .invalid-feedback, .text-danger {
            display: block;
            margin-top: 8px;
            font-size: 13px;
            color: var(--danger-color);
            padding-left: 5px;
            animation: shake 0.3s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Login Image */
        .login-img {
            position: relative;
            overflow: hidden;
            min-height: 100%;
            background: var(--dark-bg);
        }

        .login-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
            opacity: 0.9;
        }

        .login-img:hover img {
            transform: scale(1.05);
        }

        .login-img::after {
            content: '🔐 Nouveau mot de passe sécurisé';
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 18px;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            z-index: 2;
            background: rgba(0,0,0,0.4);
            padding: 10px 20px;
            border-radius: 30px;
            backdrop-filter: blur(5px);
            white-space: nowrap;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-content {
                padding: 30px 20px;
            }

            .login-img::after {
                font-size: 14px;
                padding: 8px 16px;
                white-space: normal;
                width: 90%;
                text-align: center;
            }

            .password-requirements {
                flex-direction: column;
                gap: 5px;
            }
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body class="account-page">
    <div class="main-wrapper">
        <div class="account-content">
            <div class="login-wrapper">
                <div class="login-content">
                    <div class="login-userset">
                        <!-- Logo -->
                        <div class="login-logo">
                            <img src="{{ asset('companies/logo.jpg') }}" alt="SMH">
                        </div>

                        <!-- Security Badge -->
                        <div style="text-align: center;">
                            <div class="security-badge">
                                <i class="fas fa-shield-alt"></i>
                                <span>Réinitialisation sécurisée</span>
                            </div>
                        </div>

                        <!-- Heading -->
                        <div class="login-userheading">
                            <h4>Créez votre nouveau mot de passe</h4>
                            @include('layouts.flash')
                        </div>

                        <!-- Reset Form -->
                        <form action="{{ route('password.update') }}" method="POST" id="resetForm">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">

                            <!-- Email (Read-only) -->
                            <div class="form-group">
                                <label>
                                    <i class="far fa-envelope" style="margin-right: 5px;"></i>
                                    Email
                                </label>
                                <div class="form-addons">
                                    <input 
                                        type="email" 
                                        name="email" 
                                        value="{{ $email }}" 
                                        readonly
                                        class="form-control"
                                    >
                                    <img src="{{ asset('assets/img/icons/mail.svg') }}" alt="email">
                                </div>
                                @error('email')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- New Password -->
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-lock" style="margin-right: 5px;"></i>
                                    Nouveau mot de passe
                                </label>
                                <div class="pass-group">
                                    <input 
                                        type="password" 
                                        class="pass-input" 
                                        name="password" 
                                        id="password"
                                        placeholder="Entrez votre nouveau mot de passe"
                                        required
                                    >
                                    <span class="fas toggle-password fa-eye-slash" data-target="password"></span>
                                </div>
                                @error('password')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Password Strength Meter -->
                            <div class="password-strength" id="strengthMeter">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="password-requirements" id="passwordRequirements">
                                <span class="requirement" id="length">
                                    <i class="far fa-circle"></i> 8+ caractères
                                </span>
                                <span class="requirement" id="uppercase">
                                    <i class="far fa-circle"></i> Majuscule
                                </span>
                                <span class="requirement" id="number">
                                    <i class="far fa-circle"></i> Chiffre
                                </span>
                                <span class="requirement" id="special">
                                    <i class="far fa-circle"></i> Caractère spécial
                                </span>
                            </div>

                            <!-- Confirm Password -->
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-check-circle" style="margin-right: 5px;"></i>
                                    Confirmer le mot de passe
                                </label>
                                <div class="pass-group">
                                    <input 
                                        type="password" 
                                        class="pass-input" 
                                        name="password_confirmation" 
                                        id="password_confirmation"
                                        placeholder="Confirmez votre mot de passe"
                                        required
                                    >
                                    <span class="fas toggle-password fa-eye-slash" data-target="password_confirmation"></span>
                                </div>
                                <div id="passwordMatch" style="font-size: 13px; margin-top: 5px;"></div>
                                @error('password_confirmation')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Submit Button -->
                            <div class="form-group">
                                <button type="submit" class="btn btn-login" id="submitBtn">
                                    <span>Réinitialiser le mot de passe</span>
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>

                            <!-- Back to Login -->
                            <div style="text-align: center; margin-top: 20px;">
                                <a href="{{ route('login') }}" style="color: #667eea; text-decoration: none; font-size: 14px;">
                                    <i class="fas fa-arrow-left" style="margin-right: 5px;"></i>
                                    Retour à la connexion
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Image Side -->
                <div class="login-img">
                    <img src="{{ asset('assets/img/login.jpg') }}" alt="Reset Password">
                </div>
            </div>
        </div>
    </div>

    @include('layouts.scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            document.querySelectorAll('.toggle-password').forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const targetId = this.dataset.target;
                    const input = document.getElementById(targetId);
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        this.classList.remove('fa-eye-slash');
                        this.classList.add('fa-eye');
                    } else {
                        input.type = 'password';
                        this.classList.remove('fa-eye');
                        this.classList.add('fa-eye-slash');
                    }
                });
            });

            // Password strength checker
            const password = document.getElementById('password');
            const strengthBar = document.getElementById('strengthBar');
            const requirements = {
                length: document.getElementById('length'),
                uppercase: document.getElementById('uppercase'),
                number: document.getElementById('number'),
                special: document.getElementById('special')
            };

            password.addEventListener('input', function() {
                const val = this.value;
                let strength = 0;

                // Check length
                if (val.length >= 8) {
                    requirements.length.innerHTML = '<i class="fas fa-check-circle"></i> 8+ caractères';
                    requirements.length.classList.add('valid');
                    strength++;
                } else {
                    requirements.length.innerHTML = '<i class="far fa-circle"></i> 8+ caractères';
                    requirements.length.classList.remove('valid');
                }

                // Check uppercase
                if (/[A-Z]/.test(val)) {
                    requirements.uppercase.innerHTML = '<i class="fas fa-check-circle"></i> Majuscule';
                    requirements.uppercase.classList.add('valid');
                    strength++;
                } else {
                    requirements.uppercase.innerHTML = '<i class="far fa-circle"></i> Majuscule';
                    requirements.uppercase.classList.remove('valid');
                }

                // Check number
                if (/[0-9]/.test(val)) {
                    requirements.number.innerHTML = '<i class="fas fa-check-circle"></i> Chiffre';
                    requirements.number.classList.add('valid');
                    strength++;
                } else {
                    requirements.number.innerHTML = '<i class="far fa-circle"></i> Chiffre';
                    requirements.number.classList.remove('valid');
                }

                // Check special character
                if (/[!@#$%^&*(),.?":{}|<>]/.test(val)) {
                    requirements.special.innerHTML = '<i class="fas fa-check-circle"></i> Caractère spécial';
                    requirements.special.classList.add('valid');
                    strength++;
                } else {
                    requirements.special.innerHTML = '<i class="far fa-circle"></i> Caractère spécial';
                    requirements.special.classList.remove('valid');
                }

                // Update strength bar
                if (val.length === 0) {
                    strengthBar.style.width = '0';
                    strengthBar.className = 'password-strength-bar';
                } else if (strength <= 1) {
                    strengthBar.className = 'password-strength-bar strength-weak';
                } else if (strength <= 2) {
                    strengthBar.className = 'password-strength-bar strength-medium';
                } else {
                    strengthBar.className = 'password-strength-bar strength-strong';
                }
            });

            // Password match checker
            const confirmPassword = document.getElementById('password_confirmation');
            const passwordMatch = document.getElementById('passwordMatch');

            function checkPasswordMatch() {
                if (confirmPassword.value.length > 0) {
                    if (password.value === confirmPassword.value) {
                        passwordMatch.innerHTML = '✓ Les mots de passe correspondent';
                        passwordMatch.style.color = '#28a745';
                        confirmPassword.style.borderColor = '#28a745';
                    } else {
                        passwordMatch.innerHTML = '✗ Les mots de passe ne correspondent pas';
                        passwordMatch.style.color = '#dc3545';
                        confirmPassword.style.borderColor = '#dc3545';
                    }
                } else {
                    passwordMatch.innerHTML = '';
                    confirmPassword.style.borderColor = '#e0e0e0';
                }
            }

            password.addEventListener('input', checkPasswordMatch);
            confirmPassword.addEventListener('input', checkPasswordMatch);

            // Form submission with validation
            const form = document.getElementById('resetForm');
            const submitBtn = document.getElementById('submitBtn');

            form.addEventListener('submit', function(e) {
                if (password.value !== confirmPassword.value) {
                    e.preventDefault();
                    alert('Les mots de passe ne correspondent pas !');
                } else if (password.value.length < 8) {
                    e.preventDefault();
                    alert('Le mot de passe doit contenir au moins 8 caractères !');
                } else {
                    // Show loading state
                    submitBtn.innerHTML = '<span>Réinitialisation en cours...</span> <i class="fas fa-spinner fa-spin"></i>';
                    submitBtn.disabled = true;
                }
            });

            // Add animation to image
            const loginImg = document.querySelector('.login-img');
            if (loginImg) {
                setInterval(() => {
                    loginImg.classList.add('pulse');
                    setTimeout(() => {
                        loginImg.classList.remove('pulse');
                    }, 500);
                }, 3000);
            }
        });

        // Add pulse animation CSS
        const style = document.createElement('style');
        style.textContent = `
            .login-img.pulse::after {
                transform: translateX(-50%) scale(1.05);
                background: rgba(0,0,0,0.5);
                transition: all 0.3s ease;
            }
        `;
        document.head.appendChild(style);
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>