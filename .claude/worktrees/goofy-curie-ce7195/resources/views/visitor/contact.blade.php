<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Contact — SMH</title>

    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.theme-head')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { font-family: 'Poppins', sans-serif; }
        .navbar-fixed {
            position: fixed; top: 0; width: 100%; z-index: 50;
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
        }
        .form-input {
            width: 100%; padding: 12px 16px; font-size: 14px;
            border: 1.5px solid #e5e7eb; border-radius: 12px;
            background: #fff; color: #374151; outline: none;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-input:focus {
            border-color: #c1682f;
            box-shadow: 0 0 0 3px rgba(245,158,11,0.12);
        }
        .form-input.error { border-color: #ef4444; }
        .gradient-text {
            background: linear-gradient(135deg, #c9986a, #c1682f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .info-card {
            background: #fff; border: 1.5px solid #f3f4f6;
            border-radius: 20px; padding: 2rem; text-align: center;
            transition: all 0.25s;
        }
        .info-card:hover {
            border-color: #c1682f;
            box-shadow: 0 8px 24px rgba(245,158,11,0.12);
            transform: translateY(-3px);
        }
        .info-icon {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #c9986a, #a8532a);
            border-radius: 50%; margin: 0 auto 1rem;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 22px;
        }
        .submit-btn {
            width: 100%; padding: 14px; font-size: 15px; font-weight: 700;
            background: linear-gradient(135deg, #c9986a, #a8532a);
            color: #fff; border: none; border-radius: 12px; cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
            font-family: 'Poppins', sans-serif;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .submit-btn:hover { opacity: 0.9; transform: scale(1.01); }
        .submit-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
    </style>
</head>
<body class="bg-gray-50">

    @include('partials.storefront-nav')

    {{-- HERO --}}
    <div class="pt-20 bg-gradient-to-r from-amber-500 to-amber-600">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-14 text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-3">Contactez-nous</h1>
            <p class="text-white/85 text-lg">Chez SMH, au-delà de la qualité des autres. Nous sommes à votre écoute, réponse sous 24h.</p>
        </div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">

        {{-- MESSAGES SUCCESS / ERROR --}}
        @if(session('success'))
        <div class="max-w-2xl mx-auto mb-8 bg-green-50 border border-green-200 text-green-800 rounded-16 px-6 py-4 flex items-center gap-3 rounded-xl">
            <i class="fas fa-check-circle text-green-500 text-xl"></i>
            <p class="font-medium">{{ session('success') }}</p>
        </div>
        @endif
        @if(session('error'))
        <div class="max-w-2xl mx-auto mb-8 bg-red-50 border border-red-200 text-red-800 rounded-xl px-6 py-4 flex items-center gap-3">
            <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
            <p class="font-medium">{{ session('error') }}</p>
        </div>
        @endif

        {{-- CARTES INFO --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16 max-w-4xl mx-auto">
            <div class="info-card">
                <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Adresse</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    1<sup>er</sup> adresse : Centre Faloulay, 1<sup>er</sup> étage B30, Madina route Niger<br>
                    2<sup>e</sup> adresse : T6, en face de la station Star, en partant pour Sonfonia
                </p>
            </div>
            <div class="info-card">
                <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Téléphone</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    <a href="tel:+224626311915" class="hover:text-amber-600 transition block">+224 626 311 915</a>
                    <a href="tel:+224626314400" class="hover:text-amber-600 transition block">+224 626 31 44 00</a>
                </p>
            </div>
            <div class="info-card">
                <div class="info-icon"><i class="fas fa-envelope"></i></div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Email</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    <a href="mailto:saikououmar47@gmail.com" class="hover:text-amber-600 transition break-all">
                        saikououmar47@gmail.com
                    </a>
                </p>
            </div>
        </div>

        {{-- FORMULAIRE --}}
        <div class="max-w-2xl mx-auto bg-white border border-gray-100 rounded-2xl shadow-sm p-8 md:p-10">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Envoyer un message</h2>
            <p class="text-gray-500 text-sm mb-8">Remplissez le formulaire ci-dessous et nous vous répondrons rapidement.</p>

            <form action="{{ route('contact.send') }}" method="POST" id="contact-form">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nom complet <span class="text-red-400">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="form-input {{ $errors->has('name') ? 'error' : '' }}"
                               placeholder="Votre nom">
                        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email <span class="text-red-400">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="form-input {{ $errors->has('email') ? 'error' : '' }}"
                               placeholder="votre@email.com">
                        @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Téléphone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="form-input"
                           placeholder="+224 6XX XXX XXX">
                </div>
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Sujet <span class="text-red-400">*</span></label>
                    <input type="text" name="subject" value="{{ old('subject') }}"
                           class="form-input {{ $errors->has('subject') ? 'error' : '' }}"
                           placeholder="Ex : Commande, information produit…">
                    @error('subject')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="mb-8">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Message <span class="text-red-400">*</span></label>
                    <textarea name="message" rows="5"
                              class="form-input {{ $errors->has('message') ? 'error' : '' }}"
                              placeholder="Décrivez votre demande…">{{ old('message') }}</textarea>
                    @error('message')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="submit-btn" id="submit-btn">
                    <i class="fas fa-paper-plane"></i> Envoyer le message
                </button>
            </form>
        </div>
    </div>

    @include('partials.storefront-footer')

    <script>
        document.getElementById('contact-form').addEventListener('submit', function() {
            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours…';
        });
    </script>
</body>
</html>
