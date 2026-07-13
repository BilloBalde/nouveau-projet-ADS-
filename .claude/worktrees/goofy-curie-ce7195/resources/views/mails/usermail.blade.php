<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Confirmation de compte</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fc;
            line-height: 1.6;
            color: #333;
        }
        
        .email-container {
            max-width: 600px;
            margin: 30px auto;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }
        
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 40px 30px;
            text-align: center;
        }
        
        .logo {
            margin-bottom: 15px;
        }
        
        .logo img {
            max-width: 120px;
            height: auto;
        }
        
        .header h1 {
            color: white;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        
        .header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
        }
        
        .content {
            padding: 40px 30px;
            background: white;
        }
        
        .greeting {
            font-size: 18px;
            color: #1e3c72;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .message {
            color: #555;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.8;
        }
        
        .verification-box {
            background: #f8faff;
            border: 2px dashed #2a5298;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            margin: 30px 0;
        }
        
        .verification-box p {
            color: #1e3c72;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .verify-button {
            display: inline-block;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white !important;
            text-decoration: none;
            padding: 15px 35px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(42, 82, 152, 0.3);
            transition: all 0.3s ease;
            margin: 10px 0;
        }
        
        .verify-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(42, 82, 152, 0.4);
            background: linear-gradient(135deg, #163158 0%, #1e3c72 100%);
        }
        
        .or-text {
            color: #888;
            margin: 20px 0 10px;
            font-size: 14px;
        }
        
        .direct-link {
            color: #2a5298;
            word-break: break-all;
            font-size: 14px;
            text-decoration: none;
            border-bottom: 1px solid #ddd;
            padding-bottom: 2px;
        }
        
        .direct-link:hover {
            border-bottom-color: #2a5298;
        }
        
        .info-box {
            background: #e8f4fd;
            border-radius: 10px;
            padding: 20px;
            margin: 30px 0;
            border-left: 4px solid #2a5298;
        }
        
        .info-box p {
            color: #1e3c72;
            font-size: 14px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-box i {
            font-size: 20px;
            color: #2a5298;
        }
        
        .info-box strong {
            color: #1e3c72;
            font-weight: 600;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        
        .footer p {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .social-links {
            margin: 20px 0;
        }
        
        .social-link {
            display: inline-block;
            margin: 0 10px;
            color: #2a5298;
            text-decoration: none;
            font-size: 14px;
        }
        
        .copyright {
            color: #adb5bd;
            font-size: 12px;
            margin-top: 20px;
        }
        
        .alert {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-top: 20px;
        }
        
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 12px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .verify-button {
                display: block;
                padding: 15px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header avec dégradé -->
        <div class="header">
            <div class="logo">
                <!-- Remplacez par votre logo -->
                <img src="{{ asset('assets/img/logo.png') }}" alt="EDAAG TRADING" style="max-width: 150px;">
            </div>
            <h1>Bienvenue chez EDAAG TRADING</h1>
            <p>Votre partenaire de confiance</p>
        </div>
        
        <!-- Contenu principal -->
        <div class="content">
            @if(isset($userName) && $userName)
                <div class="greeting">
                    Bonjour <strong>{{ $userName }}</strong>,
                </div>
            @endif
            
            <div class="message">
                <p>Nous vous remercions de vous être inscrit sur notre plateforme. Pour finaliser la création de votre compte et profiter de tous nos services, veuillez confirmer votre adresse email en cliquant sur le bouton ci-dessous.</p>
            </div>
            
            <!-- Boîte de vérification -->
            <div class="verification-box">
                <p style="font-size: 18px; margin-bottom: 25px;">
                    <strong>🔐 Confirmation de compte</strong>
                </p>
                
                <!-- Bouton de vérification -->
                <a href="{{ $body }}" class="verify-button">
                    ✅ CONFIRMER MON COMPTE
                </a>
                
                <div class="or-text">ou copiez ce lien</div>
                
                <!-- Lien direct -->
                <a href="{{ $body }}" class="direct-link">
                    {{ $body }}
                </a>
            </div>
            
            <!-- Informations importantes -->
            <div class="info-box">
                <p>
                    <span>⏱️</span>
                    <span><strong>Lien valable 24 heures</strong> - Pour des raisons de sécurité, ce lien de confirmation expirera dans 24 heures.</span>
                </p>
                <p>
                    <span>🛡️</span>
                    <span><strong>Sécurité</strong> - Si vous n'êtes pas à l'origine de cette inscription, veuillez ignorer cet email.</span>
                </p>
                <p>
                    <span>📱</span>
                    <span><strong>Assistance</strong> - Besoin d'aide ? Contactez-nous à edaagtrading0@gmail.com</span>
                </p>
            </div>
            
            <!-- Message supplémentaire si le body contient du HTML personnalisé -->
            @if(isset($body) && $body != $verificationLink)
                <div class="message" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                    {!! $body !!}
                </div>
            @endif
            
            <!-- Alerte pour les emails non sollicités -->
            <div class="alert">
                <strong>📧 Email automatique</strong> - Cet email a été envoyé suite à une demande d'inscription sur notre plateforme. Si vous n'avez pas effectué cette demande, aucune action n'est requise.
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>EDAAG TRADING</strong> - Votre partenaire commercial de confiance</p>
            <p>📍 Conakry, Guinée | 📞 +224 610050512 | ✉️ edaagtrading0@gmail.com</p>
            
            <div class="social-links">
                <a href="#" class="social-link">Facebook</a> •
                <a href="#" class="social-link">WhatsApp</a> •
                <a href="#" class="social-link">LinkedIn</a>
            </div>
            
            <div class="copyright">
                &copy; {{ $year ?? date('Y') }} EDAAG TRADING. Tous droits réservés.
            </div>
        </div>
    </div>
</body>
</html>