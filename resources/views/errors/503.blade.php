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
            color: #0c2c1c;
            background: linear-gradient(135deg, #f0f8f3, #ffffff 55%, #fef3f2);
        }
        .boite {
            width: 100%;
            max-width: 520px;
            text-align: center;
            background: #fff;
            border: 1px solid #dcefe3;
            border-radius: 18px;
            padding: 48px 32px;
            box-shadow: 0 24px 60px -30px rgba(12, 44, 28, .45);
        }
        .code {
            font-size: 72px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -.03em;
            background: linear-gradient(120deg, #1f6f43, #2e8b57 55%, #d92d20);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .barre {
            width: 72px;
            height: 4px;
            margin: 20px auto 24px;
            border-radius: 999px;
            background: linear-gradient(90deg, #1f6f43 0 55%, #d92d20 55% 100%);
        }
        h1 { margin: 0 0 10px; font-size: 22px; font-weight: 700; }
        p  { margin: 0 0 28px; color: #40614f; line-height: 1.6; }
        .lien {
            display: inline-block;
            padding: 13px 28px;
            border-radius: 999px;
            background: linear-gradient(135deg, #1f6f43, #195836);
            color: #fff;
            font-weight: 600;
            text-decoration: none;
        }
        .lien:hover { background: linear-gradient(135deg, #195836, #0c2c1c); }
        @media (prefers-color-scheme: dark) {
            body { color: #eaf4ee; background: linear-gradient(135deg, #061710, #0c2c1c); }
            .boite { background: #0c2c1c; border-color: #195836; }
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
