<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories — SMH</title>

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
        .cat-card {
            background: #fff; border: 1.5px solid #f3f4f6;
            border-radius: 20px; overflow: hidden;
            text-align: center; cursor: pointer;
            transition: all 0.25s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            text-decoration: none;
        }
        .cat-card:hover {
            border-color: #c1682f;
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(245,158,11,0.15);
        }
        .cat-img-wrap {
            width: 100%; height: 160px;
            overflow: hidden; background: #fafafa;
        }
        .cat-img-wrap img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform 0.3s;
        }
        .cat-card:hover .cat-img-wrap img { transform: scale(1.06); }
        .cat-icon-wrap {
            width: 100%; height: 160px;
            display: flex; align-items: center; justify-content: center;
            background: #fafafa;
        }
        .cat-icon {
            width: 80px; height: 80px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px;
            background: #f4ece1; color: #a8532a;
        }
        .cat-body { padding: 14px 12px 18px; }
        .cat-name { font-size: 14px; font-weight: 700; color: #1f2937; margin-bottom: 4px; }
        .cat-count { font-size: 12px; color: #9ca3af; }
        .cat-arrow {
            display: inline-flex; align-items: center; gap: 4px;
            margin-top: 10px; font-size: 12px; font-weight: 600;
            color: #c1682f;
        }
    </style>
</head>
<body class="bg-gray-50">

    @include('partials.storefront-nav')

    {{-- HERO --}}
    <div class="pt-20 bg-gradient-to-r from-amber-500 to-amber-600">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-3">Nos Catégories</h1>
            <p class="text-white/85 text-lg">{{ $categories->count() }} catégorie{{ $categories->count() > 1 ? 's' : '' }} disponible{{ $categories->count() > 1 ? 's' : '' }}</p>
        </div>
    </div>

    {{-- GRILLE CATÉGORIES --}}
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-14">

        {{-- Barre de recherche --}}
        <div class="max-w-sm mb-10">
            <div class="flex items-center border-2 border-gray-200 rounded-xl overflow-hidden bg-white focus-within:border-amber-400 transition">
                <input type="text" id="search" placeholder="Rechercher une catégorie…"
                       oninput="filterCats()"
                       class="flex-1 px-4 py-3 text-sm outline-none bg-transparent" style="font-family:'Poppins',sans-serif">
                <span class="px-4 text-gray-400"><i class="fas fa-search"></i></span>
            </div>
        </div>

        <div id="cat-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-5">
            @forelse($categories as $cat)
            <a href="{{ route('products.index') }}?cat={{ urlencode($cat->name) }}"
               class="cat-card" data-name="{{ strtolower($cat->name) }}">
                @if($cat->image_url)
                <div class="cat-img-wrap">
                    <img src="{{ $cat->image_url }}" alt="{{ $cat->name }}"
                         onerror="this.parentElement.outerHTML='<div class=\'cat-icon-wrap\'><div class=\'cat-icon\'><i class=\'fas fa-box\'></i></div></div>'">
                </div>
                @else
                <div class="cat-icon-wrap">
                    <div class="cat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
                @endif
                <div class="cat-body">
                    <div class="cat-name">{{ $cat->name }}</div>
                    <div class="cat-count">{{ $cat->products_count }} produit{{ $cat->products_count > 1 ? 's' : '' }}</div>
                    <div class="cat-arrow">Voir <i class="fas fa-arrow-right text-xs"></i></div>
                </div>
            </a>
            @empty
            <div class="col-span-full text-center text-gray-400 py-16">
                <i class="fas fa-folder-open text-5xl mb-4 block"></i>
                Aucune catégorie disponible pour le moment.
            </div>
            @endforelse
        </div>

        <div id="no-result" class="hidden text-center text-gray-400 py-16">
            <i class="fas fa-search text-5xl mb-4 block"></i>
            Aucune catégorie trouvée.
        </div>
    </div>

    @include('partials.storefront-footer')

    <script>
        function filterCats() {
            const q = document.getElementById('search').value.toLowerCase();
            const cards = document.querySelectorAll('.cat-card');
            let visible = 0;
            cards.forEach(c => {
                const match = c.dataset.name.includes(q);
                c.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            document.getElementById('no-result').classList.toggle('hidden', visible > 0);
        }
    </script>
</body>
</html>
