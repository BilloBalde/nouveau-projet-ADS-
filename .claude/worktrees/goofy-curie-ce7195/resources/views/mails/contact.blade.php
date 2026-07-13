<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nouveau message de contact</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            line-height: 1.6;
            padding: 30px 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .email-container {
            max-width: 650px;
            margin: 0 auto;
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Header */
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .email-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 50%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .header-icon {
            width: 90px;
            height: 90px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(5px);
            position: relative;
            z-index: 1;
        }
        
        .header-icon span {
            font-size: 40px;
            line-height: 1;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        }
        
        .email-header h1 {
            color: white;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .email-header p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 18px;
            position: relative;
            z-index: 1;
        }
        
        /* Content */
        .email-content {
            padding: 40px 35px;
            background: white;
        }
        
        .badge {
            display: inline-block;
            background: #667eea15;
            color: #667eea;
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 25px;
            border: 1px solid #667eea30;
            letter-spacing: 0.5px;
        }
        
        .badge i {
            margin-right: 8px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: #f8faff;
            border-radius: 20px;
            padding: 25px 20px;
            border: 1px solid #e8ecf5;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.15);
        }
        
        .info-label {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #999;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-label i {
            color: #667eea;
            font-size: 16px;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            word-break: break-word;
        }
        
        .info-value.small {
            font-size: 15px;
            font-weight: 500;
            color: #666;
        }
        
        /* Message Box */
        .message-box {
            background: #f8faff;
            border-radius: 20px;
            padding: 25px;
            margin: 30px 0;
            border-left: 4px solid #667eea;
            border: 1px solid #e8ecf5;
            border-left-width: 4px;
        }
        
        .message-box h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message-box h3 i {
            color: #667eea;
        }
        
        .message-content {
            background: white;
            border-radius: 15px;
            padding: 20px;
            font-size: 16px;
            line-height: 1.8;
            color: #444;
            border: 1px solid #e8ecf5;
            white-space: pre-line;
        }
        
        /* Metadata */
        .metadata {
            background: #f8faff;
            border-radius: 15px;
            padding: 20px;
            margin: 25px 0;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px dashed #667eea40;
        }
        
        .metadata-icon {
            width: 50px;
            height: 50px;
            background: #667eea15;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-size: 24px;
        }
        
        .metadata-text {
            flex: 1;
        }
        
        .metadata-text p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .metadata-text strong {
            color: #333;
            display: block;
            margin-bottom: 5px;
            font-size: 16px;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin: 35px 0 25px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 25px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 160px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f1f3f5;
            color: #667eea;
            border: 1px solid #e9ecef;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        /* Footer */
        .email-footer {
            background: #f8faff;
            padding: 30px 35px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        
        .company-name {
            font-size: 20px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
        }
        
        .contact-info {
            display: flex;
            justify-content: center;
            gap: 25px;
            flex-wrap: wrap;
            margin: 20px 0;
        }
        
        .contact-info span {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }
        
        .contact-info i {
            color: #667eea;
        }
        
        .copyright {
            color: #999;
            font-size: 13px;
            margin-top: 20px;
        }
        
        hr {
            border: none;
            border-top: 2px solid #e9ecef;
            margin: 20px 0;
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            .email-container {
                border-radius: 20px;
            }
            
            .email-header {
                padding: 30px 20px;
            }
            
            .email-content {
                padding: 25px 20px;
            }
            
            .email-footer {
                padding: 25px 20px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .header-icon {
                width: 70px;
                height: 70px;
            }
            
            .header-icon span {
                font-size: 32px;
            }
            
            .email-header h1 {
                font-size: 24px;
            }
        }
        
        /* Animations */
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        /* Quote styling */
        .quote {
            font-style: italic;
            color: #666;
            border-left: 3px solid #667eea;
            padding-left: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="header-icon pulse">
                <span>📬</span>
            </div>
            <h1>Nouveau message de contact</h1>
            <p>Vous avez reçu un message via le formulaire de contact</p>
        </div>
        
        <!-- Content -->
        <div class="email-content">
            <!-- Badge -->
            <div class="badge">
                <i class="fas fa-envelope-open-text"></i>
                NOUVEAU MESSAGE
            </div>
            
            <!-- Sender Information Grid -->
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-label">
                        <i class="fas fa-user"></i>
                        EXPÉDITEUR
                    </div>
                    <div class="info-value">{{ $senderName ?? 'Non spécifié' }}</div>
                    <div class="info-value small" style="margin-top: 8px;">
                        <i class="fas fa-tag" style="margin-right: 5px;"></i>
                        Client
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-label">
                        <i class="fas fa-envelope"></i>
                        EMAIL
                    </div>
                    <div class="info-value">{{ $senderEmail ?? 'Non spécifié' }}</div>
                    <div class="info-value small" style="margin-top: 8px;">
                        <i class="fas fa-check-circle" style="color: #28a745; margin-right: 5px;"></i>
                        Valide
                    </div>
                </div>
            </div>
            
            <!-- Subject -->
            <div style="margin-bottom: 25px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <span style="background: #667eea; width: 4px; height: 25px; border-radius: 2px;"></span>
                    <h3 style="color: #333; font-size: 18px; margin: 0;">Sujet du message</h3>
                </div>
                <div style="background: #f8faff; border-radius: 12px; padding: 15px 20px; font-weight: 500; color: #333; border: 1px solid #e8ecf5;">
                    {{ $subject ?? 'Sans sujet' }}
                </div>
            </div>
            
            <!-- Message Box -->
            <div class="message-box">
                <h3>
                    <i class="fas fa-quote-left"></i>
                    Message
                </h3>
                <div class="message-content">
                    {!! nl2br(e($messageContent ?? 'Message vide')) !!}
                </div>
            </div>
            
            <!-- Metadata -->
            <div class="metadata">
                <div class="metadata-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="metadata-text">
                    <strong>Date et heure d'envoi</strong>
                    <p>{{ isset($sentAt) ? $sentAt->format('d/m/Y à H:i') : now()->format('d/m/Y à H:i') }}</p>
                    <p style="font-size: 12px; color: #999;">Fuseau horaire: GMT+0</p>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="mailto:{{ $senderEmail }}" class="btn btn-primary">
                    <i class="fas fa-reply"></i>
                    Répondre à {{ explode(' ', $senderName ?? '')[0] ?? 'l\'expéditeur' }}
                </a>
                <a href="#" class="btn btn-secondary">
                    <i class="fas fa-archive"></i>
                    Archiver
                </a>
            </div>
            
            <!-- Quick Response Template -->
            <div style="background: #f8faff; border-radius: 12px; padding: 20px; margin: 20px 0;">
                <p style="color: #667eea; font-weight: 600; margin-bottom: 10px;">
                    <i class="fas fa-lightbulb" style="margin-right: 8px;"></i>
                    Suggestion de réponse rapide:
                </p>
                <div class="quote">
                    Bonjour {{ $senderName ?? '' }},<br><br>
                    Nous avons bien reçu votre message et vous remercions de nous avoir contactés.<br>
                    Notre équipe va étudier votre demande et vous répondra dans les plus brefs délais.<br><br>
                    Cordialement,<br>
                    L'équipe EDAAG TRADING
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="email-footer">
            <div class="company-name">
                ⚡ EDAAG TRADING
            </div>
            
            <div class="contact-info">
                <span>
                    <i class="fas fa-phone-alt"></i>
                    +224 610050512
                </span>
                <span>
                    <i class="fas fa-envelope"></i>
                    edaagtrading0@gmail.com
                </span>
                <span>
                    <i class="fas fa-map-marker-alt"></i>
                    Conakry, Guinée
                </span>
            </div>
            
            <hr>
            
            <div style="color: #666; font-size: 14px; line-height: 1.8; margin: 20px 0;">
                <p style="margin-bottom: 5px;">
                    <i class="fas fa-info-circle" style="color: #667eea;"></i>
                    Cet email a été envoyé automatiquement depuis le formulaire de contact de votre site web.
                </p>
                <p style="font-size: 12px; color: #999;">
                    IP: {{ request()->ip() ?? 'Non disponible' }} | 
                    User Agent: {{ substr(request()->userAgent() ?? 'Non disponible', 0, 50) }}...
                </p>
            </div>
            
            <div class="copyright">
                © {{ $year ?? date('Y') }} EDAAG TRADING. Tous droits réservés.<br>
                Ce message est confidentiel et destiné uniquement à l'équipe EDAAG TRADING.
            </div>
        </div>
    </div>
    
    <!-- Font Awesome (for email clients that support it) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>