<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="8;url={{ route('login') }}">
    <title>Session Expired</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"> <!-- Include your custom CSS -->
</head>
<body>
    <div class="container text-center mt-5">
        <h1>Oops! Votre session a expire.</h1>
        <p>Veuillez vous connecter a nouveau.</p>
        <a href="{{ route('login') }}" class="btn btn-primary">Vers la page de Connection</a>
        <p>Cliquer le bouton ci-dessus pour se connecter.</p>
    </div>
</body>
</html>
