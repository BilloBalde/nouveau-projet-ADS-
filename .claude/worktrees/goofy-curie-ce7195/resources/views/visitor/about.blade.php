<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À propos — SMH</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.theme-head')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>* { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="bg-gray-50">

    @include('partials.storefront-nav')

    <!-- Hero -->
    <section class="pt-24 pb-16 bg-gradient-to-br from-amber-50 to-orange-100">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h1 class="text-4xl font-black text-gray-900 mb-4">À propos de <span class="text-amber-500">SMH</span></h1>
            <p class="text-lg text-gray-600 leading-relaxed">
                Chez SMH, au-delà de la qualité des autres — votre partenaire de confiance pour l'ameublement en Guinée.
            </p>
        </div>
    </section>

    <!-- Contenu -->
    <section class="py-16">
        <div class="max-w-5xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center mb-16">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Notre mission</h2>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        SMH est une entreprise guinéenne spécialisée dans l'ameublement : salons, chambres,
                        salles à manger, bureaux et pièces sur mesure. Nous mettons à disposition de nos clients
                        des meubles adaptés à leurs besoins, avec un accompagnement fiable et des standards élevés.
                    </p>
                    <p class="text-gray-600 leading-relaxed">
                        Notre équipe expérimentée vous conseille selon votre espace et votre budget, pour des meubles
                        qui reflètent votre style et transforment durablement votre intérieur.
                    </p>
                </div>
                <div class="bg-amber-50 rounded-2xl p-8 text-center">
                    <div class="text-6xl text-amber-400 mb-4"><i class="fas fa-couch"></i></div>
                    <div class="text-3xl font-black text-gray-900">SMH</div>
                    <div class="text-amber-600 font-semibold mt-1">Qualité & Service</div>
                </div>
            </div>

            <!-- Valeurs -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
                @foreach([
                    ['icon' => 'fa-star', 'title' => 'Qualité', 'text' => 'Bois massif, finitions soignées et assemblage solide, pour des meubles conçus pour durer.'],
                    ['icon' => 'fa-bolt', 'title' => 'Rapidité', 'text' => 'Délais de livraison optimisés partout en Guinée, en 24 à 48h.'],
                    ['icon' => 'fa-handshake', 'title' => 'Fiabilité', 'text' => 'Un service client à votre écoute du lundi au samedi.'],
                ] as $val)
                <div class="bg-white rounded-2xl p-6 shadow-sm text-center">
                    <div class="w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas {{ $val['icon'] }} text-amber-500 text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">{{ $val['title'] }}</h3>
                    <p class="text-gray-500 text-sm">{{ $val['text'] }}</p>
                </div>
                @endforeach
            </div>

            <!-- Adresses -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-16">
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-map-marker-alt text-amber-500"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-1">1<sup>ère</sup> adresse</h3>
                    <p class="text-gray-500 text-sm">Centre Faloulay, 1<sup>er</sup> étage B30, Madina route Niger</p>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-map-marker-alt text-amber-500"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-1">2<sup>e</sup> adresse</h3>
                    <p class="text-gray-500 text-sm">T6, en face de la station Star, en partant pour Sonfonia</p>
                </div>
            </div>

            <!-- CTA -->
            <div class="text-center">
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl transition">
                    <i class="fas fa-envelope"></i> Nous contacter
                </a>
                <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-8 py-3 rounded-xl transition ml-3">
                    <i class="fas fa-th-large"></i> Voir le catalogue
                </a>
            </div>
        </div>
    </section>

    @include('partials.storefront-footer')

</body>
</html>
