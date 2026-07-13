<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMH — Connexion Manager</title>

    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        h1, h2, h3 { font-family: 'Fraunces', serif; }
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            background: #f8f7f5;
        }

        /* ── Panneau gauche ── */
        .left-panel {
            flex: 0 0 45%;
            background: linear-gradient(160deg, #2b1d14 0%, #4a2f1a 45%, #7a3f1f 80%, #c1682f 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 52px;
            position: relative;
            overflow: hidden;
        }
        .left-panel::before {
            content: '';
            position: absolute; top: -120px; right: -120px;
            width: 420px; height: 420px;
            background: radial-gradient(circle, rgba(193,104,47,0.2) 0%, transparent 65%);
            border-radius: 50%;
            pointer-events: none;
        }
        .left-panel::after {
            content: '';
            position: absolute; bottom: -100px; left: -80px;
            width: 340px; height: 340px;
            background: radial-gradient(circle, rgba(168,83,42,0.15) 0%, transparent 65%);
            border-radius: 50%;
            pointer-events: none;
        }

        /* Logo */
        .brand {
            display: flex; align-items: center; gap: 14px;
            position: relative; z-index: 2;
        }
        .brand img {
            height: 48px; width: auto; object-fit: contain;
            filter: brightness(0) invert(1);
        }
        .brand-name {
            font-size: 1.4rem; font-weight: 800;
            color: #fff; letter-spacing: -0.5px;
        }
        .brand-sub {
            font-size: 0.7rem; color: rgba(255,255,255,0.55);
            font-weight: 400; letter-spacing: 1px; text-transform: uppercase;
            margin-top: 1px;
        }

        /* Centre gauche */
        .left-center {
            position: relative; z-index: 2;
        }
        .left-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.18);
            backdrop-filter: blur(6px);
            border-radius: 999px;
            padding: 6px 16px;
            color: rgba(255,255,255,0.85);
            font-size: 0.75rem; font-weight: 500;
            margin-bottom: 28px;
        }
        .left-badge span {
            width: 7px; height: 7px;
            background: #c9986a; border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%,100% { opacity: 1; transform: scale(1); }
            50%      { opacity: 0.5; transform: scale(1.3); }
        }
        .left-title {
            font-size: 2.4rem; font-weight: 800;
            color: #fff; line-height: 1.2;
            margin-bottom: 16px; letter-spacing: -0.5px;
        }
        .left-title em {
            font-style: normal;
            background: linear-gradient(90deg, #c9986a, #f0dcc0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .left-desc {
            font-size: 0.88rem; color: rgba(255,255,255,0.65);
            line-height: 1.7; max-width: 320px;
            margin-bottom: 40px;
        }

        /* Stats */
        .left-stats {
            display: flex; gap: 16px;
        }
        .stat-box {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            backdrop-filter: blur(8px);
            border-radius: 14px;
            padding: 14px 20px;
            text-align: center;
            flex: 1;
        }
        .stat-num {
            font-size: 1.5rem; font-weight: 800;
            color: #c9986a;
        }
        .stat-lbl {
            font-size: 0.7rem; color: rgba(255,255,255,0.55);
            margin-top: 2px;
        }

        /* Bas gauche */
        .left-footer {
            position: relative; z-index: 2;
            font-size: 0.72rem; color: rgba(255,255,255,0.35);
        }

        /* Icônes flottants */
        .floating-icons {
            position: absolute; inset: 0;
            pointer-events: none; z-index: 1;
            overflow: hidden;
        }
        .fi {
            position: absolute;
            width: 52px; height: 52px;
            border-radius: 14px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            display: flex; align-items: center; justify-content: center;
            color: rgba(255,255,255,0.3);
            font-size: 20px;
            animation: floatUp 6s ease-in-out infinite;
        }
        .fi:nth-child(1) { top: 22%; right: 12%; animation-delay: 0s; }
        .fi:nth-child(2) { top: 55%; right: 6%;  animation-delay: 1.5s; font-size: 16px; width: 40px; height: 40px; }
        .fi:nth-child(3) { top: 38%; right: 22%; animation-delay: 3s; font-size: 14px; width: 36px; height: 36px; }
        @keyframes floatUp {
            0%,100% { transform: translateY(0) rotate(0deg); }
            50%      { transform: translateY(-14px) rotate(6deg); }
        }

        /* ── Panneau droit ── */
        .right-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
        }
        .form-card {
            width: 100%;
            max-width: 420px;
        }

        .form-header { margin-bottom: 36px; }
        .form-title {
            font-size: 1.75rem; font-weight: 800;
            color: #1a1a1a; margin-bottom: 6px;
        }
        .form-subtitle {
            font-size: 0.85rem; color: #9ca3af;
        }

        /* Alerts */
        .alert-error {
            background: #fef2f2; border: 1px solid #fecaca;
            border-radius: 12px; padding: 12px 16px;
            font-size: 0.82rem; color: #b91c1c;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }
        .alert-success {
            background: #f0fdf4; border: 1px solid #bbf7d0;
            border-radius: 12px; padding: 12px 16px;
            font-size: 0.82rem; color: #15803d;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }

        /* Champs */
        .field { margin-bottom: 20px; }
        .field label {
            display: block; font-size: 0.8rem;
            font-weight: 600; color: #374151;
            margin-bottom: 8px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap .icon-left {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            color: #a8532a; font-size: 15px;
            pointer-events: none;
        }
        .input-wrap input {
            width: 100%;
            padding: 13px 16px 13px 44px;
            font-size: 0.88rem;
            font-family: 'Poppins', sans-serif;
            background: #fafafa;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            color: #111827;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .input-wrap input:focus {
            background: #fff;
            border-color: #c1682f;
            box-shadow: 0 0 0 4px rgba(193,104,47,0.1);
        }
        .input-wrap input::placeholder { color: #c4c4c4; }

        /* Toggle mdp */
        .toggle-pw {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #9ca3af; font-size: 15px;
            transition: color 0.2s;
            padding: 4px;
        }
        .toggle-pw:hover { color: #a8532a; }

        /* Lien oublié */
        .forgot-row {
            display: flex; justify-content: flex-end;
            margin-top: -8px; margin-bottom: 24px;
        }
        .forgot-row a {
            font-size: 0.78rem; color: #a8532a;
            text-decoration: none; font-weight: 500;
            transition: color 0.2s;
        }
        .forgot-row a:hover { color: #7a3a19; }

        /* Bouton submit */
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #c9986a 0%, #a8532a 100%);
            color: #fff; font-size: 0.95rem; font-weight: 700;
            font-family: 'Poppins', sans-serif;
            border: none; border-radius: 12px;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 16px rgba(168,83,42,0.35);
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-submit:hover {
            opacity: 0.93;
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(168,83,42,0.4);
        }
        .btn-submit:active { transform: translateY(0); }

        /* Retour accueil */
        .back-link {
            display: flex; align-items: center; justify-content: center;
            gap: 6px; margin-top: 24px;
            font-size: 0.8rem; color: #9ca3af;
            text-decoration: none;
            transition: color 0.2s;
        }
        .back-link:hover { color: #a8532a; }

        /* Responsive */
        @media (max-width: 768px) {
            .left-panel { display: none; }
            body { background: #fff; }
            .right-panel { padding: 32px 20px; }
        }
    </style>
</head>
<body>

    {{-- ── Panneau gauche (branding) ── --}}
    <div class="left-panel">
        <div class="floating-icons">
            <div class="fi"><i class="fas fa-couch"></i></div>
            <div class="fi"><i class="fas fa-chair"></i></div>
            <div class="fi"><i class="fas fa-warehouse"></i></div>
        </div>

        {{-- Logo --}}
        <div class="brand">
            <img src="{{ asset('images/customers/logo.jpg') }}" alt="SMH"
                 style="filter: none;">
            <div>
                <div class="brand-name">SMH</div>
                <div class="brand-sub">Espace Manager</div>
            </div>
        </div>

        {{-- Texte central --}}
        <div class="left-center">
            <div class="left-badge">
                <span></span>
                Système de gestion interne
            </div>
            <h1 class="left-title">
                Gérez votre<br>
                activité <em>d'ameublement</em><br>
                en toute simplicité
            </h1>
            <p class="left-desc">
                Pilotez vos stocks, ventes, boutiques et commandes de meubles depuis un seul espace sécurisé.
            </p>
            <div class="left-stats">
                <div class="stat-box">
                    <div class="stat-num"><i class="fas fa-box-open" style="font-size:1.2rem;"></i></div>
                    <div class="stat-lbl">Stocks</div>
                </div>
                <div class="stat-box">
                    <div class="stat-num"><i class="fas fa-chart-line" style="font-size:1.2rem;"></i></div>
                    <div class="stat-lbl">Ventes</div>
                </div>
                <div class="stat-box">
                    <div class="stat-num"><i class="fas fa-users" style="font-size:1.2rem;"></i></div>
                    <div class="stat-lbl">Clients</div>
                </div>
            </div>
        </div>

        <div class="left-footer">
            &copy; {{ date('Y') }} SMH — Conakry, Guinée
        </div>
    </div>

    {{-- ── Panneau droit (formulaire) ── --}}
    <div class="right-panel">
        <div class="form-card">

            <div class="form-header">
                <h2 class="form-title">Bon retour 👋</h2>
                <p class="form-subtitle">Connectez-vous à votre espace Administrateur</p>
            </div>

            {{-- Messages flash --}}
            @if(session('error'))
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ session('error') }}
                </div>
            @endif
            @if(session('success'))
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if($errors->any())
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login_submit') }}" method="POST" autocomplete="off">
                @csrf

                {{-- Email --}}
                <div class="field">
                    <label for="email">Adresse email</label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope icon-left"></i>
                        <input type="email" id="email" name="email"
                               value="{{ old('email') }}"
                               placeholder="manager@smh.com"
                               required autofocus>
                    </div>
                </div>

                {{-- Mot de passe --}}
                <div class="field">
                    <label for="password">Mot de passe</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock icon-left"></i>
                        <input type="password" id="password" name="password"
                               placeholder="••••••••" required>
                        <button type="button" class="toggle-pw" id="togglePw" aria-label="Afficher/masquer">
                            <i class="fas fa-eye-slash" id="pwIcon"></i>
                        </button>
                    </div>
                </div>

                {{-- Mot de passe oublié --}}
                <div class="forgot-row">
                    <a href="{{ route('forgotPass') }}">
                        <i class="fas fa-key" style="font-size:11px;margin-right:4px;"></i>
                        Mot de passe oublié ?
                    </a>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn-submit">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </button>
            </form>

            <a href="{{ route('accueil') }}" class="back-link">
                <i class="fas fa-arrow-left" style="font-size:11px;"></i>
                Retour au site public
            </a>

        </div>
    </div>

    <script>
        const togglePw = document.getElementById('togglePw');
        const pwInput  = document.getElementById('password');
        const pwIcon   = document.getElementById('pwIcon');

        togglePw.addEventListener('click', () => {
            const isHidden = pwInput.type === 'password';
            pwInput.type = isHidden ? 'text' : 'password';
            pwIcon.className = isHidden ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    </script>
</body>
</html>
