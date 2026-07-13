<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modification de mot de passe</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .card {
            max-width: 480px;
            margin: 20px auto;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        }
        .icon-header {
            background: #1a5cff;
            padding: 30px;
            text-align: center;
        }
        .icon-header span {
            font-size: 48px;
            background: rgba(255,255,255,0.2);
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            display: inline-block;
        }
        .content {
            padding: 30px;
        }
        h2 {
            font-size: 24px;
            color: #111;
            margin: 0 0 10px 0;
        }
        .message {
            color: #666;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .info-box {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 20px;
            margin: 25px 0;
        }
        .warning {
            background: #ffebee;
            border-radius: 12px;
            padding: 15px;
            color: #c62828;
            font-size: 14px;
            border-left: 4px solid #c62828;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #999;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-header">
            <span>🔐</span>
        </div>
        <div class="content">
            <h2>Modification de mot de passe</h2>
            
            @if(isset($userName))
                <p class="message">Bonjour <strong>{{ $userName }}</strong>,</p>
            @endif
            
            <p class="message">{{ $body }}</p>
            
            <div class="info-box">
                <p style="margin: 0;"><strong>Date:</strong> {{ date('d/m/Y H:i') }}</p>
            </div>
            
            <div class="warning">
                <strong>⚠️ Vous n'avez pas demandé ce changement ?</strong><br>
                Contactez immédiatement un administrateur à edaagtrading0@gmail.com
            </div>
        </div>
        <div class="footer">
            © {{ date('Y') }} EDAAG TRADING - Tous droits réservés
        </div>
    </div>
</body>
</html>