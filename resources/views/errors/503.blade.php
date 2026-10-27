{{--
    Page affichée pendant une maintenance (php artisan down), à chaque mise
    à jour du site. Mêmes contraintes que la 500 : totalement autonome, aucune
    dépendance qui pourrait échouer pendant que l'application est arrêtée.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance en cours — {{ config('app.name') }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--adm-brand-900,#1C230D);
            background: linear-gradient(135deg, var(--adm-brand-50,#F4F5EC), #ffffff 55%, #FBF6EA);
        }
        .boite {
            width: 100%;
            max-width: 520px;
            text-align: center;
            background: #fff;
            border: 1px solid var(--adm-brand-100,#E6E9D5);
            border-radius: 18px;
            padding: 48px 32px;
            box-shadow: 0 24px 60px -30px rgba(28,35,13, .45);
        }
        .code {
            font-size: 72px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -.03em;
            background: linear-gradient(120deg, var(--adm-brand-600,#3E4C21), var(--adm-brand-500,#6B7A35) 55%, var(--adm-accent,#C7A150));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .barre {
            width: 72px;
            height: 4px;
            margin: 20px auto 24px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--adm-brand-600,#3E4C21) 0 55%, var(--adm-accent,#C7A150) 55% 100%);
        }
        h1 { margin: 0 0 10px; font-size: 22px; font-weight: 700; }
        p  { margin: 0 0 28px; color: #40614f; line-height: 1.6; }
        .lien {
            display: inline-block;
            padding: 13px 28px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--adm-brand-600,#3E4C21), var(--adm-brand-700,#323D1A));
            color: #fff;
            font-weight: 600;
            text-decoration: none;
        }
        .lien:hover { background: linear-gradient(135deg, var(--adm-brand-700,#323D1A), var(--adm-brand-900,#1C230D)); }
        @media (prefers-color-scheme: dark) {
            body { color: #eaf4ee; background: linear-gradient(135deg, var(--adm-brand-950,#10150A), var(--adm-brand-900,#1C230D)); }
            .boite { background: var(--adm-brand-900,#1C230D); border-color: var(--adm-brand-700,#323D1A); }
            p { color: #a9c9b7; }
        }
    </style>
</head>
<body>
    <div class="boite">
        <div class="code">503</div>
        <div class="barre"></div>
        <h1>Site en maintenance</h1>
        <p>Une mise à jour est en cours. Le site sera de nouveau accessible dans quelques minutes.<br>Merci de votre patience.</p>
        <a class="lien" href="{{ url('/') }}">Retour à l'accueil</a>
    </div>
</body>
</html>
