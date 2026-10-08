<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À propos — TELICO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.theme-head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>* { font-family: 'Source Sans 3', sans-serif; }</style>
</head>
<body class="bg-gray-50">

    @include('partials.storefront-nav')

    <!-- Hero -->
    <section class="pt-24 pb-16 bg-gradient-to-br from-brand-50 to-accent-100">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h1 class="text-4xl font-black text-gray-900 mb-4">À propos de <span class="text-brand-500">TELICO</span></h1>
            <p class="text-lg text-gray-600 leading-relaxed">
                Chez TELICO, au-delà de la qualité des autres — votre quincaillerie générale de confiance à Conakry.
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
                        TELICO est une quincaillerie générale guinéenne à Conakry : peinture, outillage,
                        serrurerie, plomberie et électricité. Nous mettons à disposition de nos clients des articles
                        adaptés à leurs travaux, avec un accompagnement fiable et des standards élevés.
                    </p>
                    <p class="text-gray-600 leading-relaxed">
                        Notre équipe expérimentée vous conseille selon votre chantier et votre budget, pour des articles
                        de quincaillerie solides et adaptés à vos besoins.
                    </p>
                </div>
                <div class="bg-brand-50 rounded-2xl p-8 text-center">
                    <div class="text-6xl text-brand-400 mb-4"><i class="fas fa-toolbox"></i></div>
                    <div class="text-3xl font-black text-gray-900">TELICO</div>
                    <div class="text-brand-600 font-semibold mt-1">Qualité & Service</div>
                </div>
            </div>

            <!-- Valeurs -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
                @foreach([
                    ['icon' => 'fa-star', 'title' => 'Qualité', 'text' => 'Articles solides et fiables, sélectionnés pour durer sur vos chantiers.'],
                    ['icon' => 'fa-bolt', 'title' => 'Rapidité', 'text' => 'Délais de livraison optimisés partout en Guinée, en 24 à 48h.'],
                    ['icon' => 'fa-handshake', 'title' => 'Fiabilité', 'text' => 'Un service client à votre écoute du lundi au samedi.'],
                ] as $val)
                <div class="bg-white rounded-2xl p-6 shadow-sm text-center">
                    <div class="w-14 h-14 bg-brand-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas {{ $val['icon'] }} text-brand-500 text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">{{ $val['title'] }}</h3>
                    <p class="text-gray-500 text-sm">{{ $val['text'] }}</p>
                </div>
                @endforeach
            </div>

            <!-- Adresses -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-16">
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <div class="w-12 h-12 bg-brand-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-map-marker-alt text-brand-500"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-1">Quincaillerie Madina</h3>
                    <p class="text-gray-500 text-sm">Madina, Ecole Gare Routière Dabola Camion, Conakry</p>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <div class="w-12 h-12 bg-brand-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-map-marker-alt text-brand-500"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-1">Dépôt Madina</h3>
                    <p class="text-gray-500 text-sm">Madina, Conakry (réapprovisionnement)</p>
                </div>
            </div>

            <!-- CTA -->
            <div class="text-center">
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white font-semibold px-8 py-3 rounded-xl transition">
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
