@php
    // Numéro principal de la boutique, au format international requis par wa.me et tel:
    $telPrincipal = '224625453545';
    $telSecondaire = '224628593110';
    $emailBoutique = 'abdulghadirybalde@gmail.com';
@endphp
<footer class="text-gray-300 py-14" style="background:linear-gradient(180deg,var(--brand-950),#04120c);">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <div>
                <div class="flex items-center gap-2.5 mb-4">
                    {{-- shrink-0 : sans lui, la colonne étroite du pied de page écrasait
                         le logo à 0px de large. Pastille blanche pour la même raison que
                         dans le bandeau : le fond du PNG est opaque. --}}
                    <span class="flex items-center justify-center bg-white rounded-lg px-2 py-1 shrink-0">
                        <img src="{{ asset('companies/ADK.png') }}" alt="Coleah Bandeya & Fils" class="h-6 w-auto object-contain">
                    </span>
                    <span class="font-bold text-white">Coleah Bandeya & Fils</span>
                </div>
                <p class="text-sm text-gray-400 mb-4">Quincaillerie générale — peinture, outillage, serrurerie, plomberie et électricité — basée à Conakry, Guinée.</p>
                <p class="text-sm text-gray-400">
                    <i class="fas fa-map-marker-alt text-brand-300 mr-1.5"></i>
                    Madina, Ecole Gare Routière Dabola Camion
                </p>
            </div>

            {{-- Ces colonnes ne listent que des pages qui existent réellement : les
                 anciens liens Blog, Recrutement, FAQ, Politique de retour et Mentions
                 légales pointaient tous vers « # », sans route correspondante. --}}
            <div>
                <h4 class="font-bold text-white mb-4">Boutique</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('accueil') }}" class="text-gray-400 hover:text-white transition">Accueil</a></li>
                    <li><a href="{{ route('products.index') }}" class="text-gray-400 hover:text-white transition">Catalogue</a></li>
                    <li><a href="{{ route('public.categories') }}" class="text-gray-400 hover:text-white transition">Nos rayons</a></li>
                    <li><a href="{{ route('about') }}" class="text-gray-400 hover:text-white transition">À propos</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-white mb-4">Votre commande</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('panier') }}" class="text-gray-400 hover:text-white transition">Mon panier</a></li>
                    <li><a href="{{ route('otp.login') }}" class="text-gray-400 hover:text-white transition">Espace client</a></li>
                    <li><a href="{{ route('contact') }}" class="text-gray-400 hover:text-white transition">Nous écrire</a></li>
                </ul>
            </div>

            {{-- Contacts réels et cliquables, à la place des icônes de réseaux sociaux
                 qui pointaient vers « # » : la boutique n'a pas de comptes connus. --}}
            <div>
                <h4 class="font-bold text-white mb-4">Nous joindre</h4>
                <ul class="space-y-3 text-sm">
                    <li>
                        <a href="https://wa.me/{{ $telPrincipal }}" target="_blank" rel="noopener"
                           class="flex items-center gap-2.5 text-gray-400 hover:text-white transition">
                            <span class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center text-white shrink-0">
                                <i class="fab fa-whatsapp"></i>
                            </span>
                            WhatsApp
                        </a>
                    </li>
                    <li>
                        <a href="tel:+{{ $telPrincipal }}" class="flex items-center gap-2.5 text-gray-400 hover:text-white transition">
                            <span class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center text-white shrink-0">
                                <i class="fas fa-phone-alt text-xs"></i>
                            </span>
                            625 45 35 45
                        </a>
                    </li>
                    <li>
                        <a href="tel:+{{ $telSecondaire }}" class="flex items-center gap-2.5 text-gray-400 hover:text-white transition">
                            <span class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center text-white shrink-0">
                                <i class="fas fa-phone-alt text-xs"></i>
                            </span>
                            628 59 31 10
                        </a>
                    </li>
                    <li>
                        <a href="mailto:{{ $emailBoutique }}" class="flex items-center gap-2.5 text-gray-400 hover:text-white transition">
                            <span class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center text-white shrink-0">
                                <i class="fas fa-envelope text-xs"></i>
                            </span>
                            <span class="break-all">E-mail</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        {{-- Réseaux sociaux : conservés à la demande du client. Les comptes n'existent
             pas encore, les liens restent donc en « # » — il suffira de remplacer le
             href de chaque icône par l'adresse réelle le jour venu. --}}
        <div class="border-t border-white/10 pt-8 text-center">
            <h4 class="font-bold text-white mb-4 text-sm">Suivez-nous</h4>
            <div class="flex justify-center gap-3 mb-8">
                <a href="#" title="Facebook" aria-label="Facebook"
                   class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-brand-600 transition"><i class="fab fa-facebook-f"></i></a>
                <a href="#" title="Instagram" aria-label="Instagram"
                   class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-brand-600 transition"><i class="fab fa-instagram"></i></a>
                <a href="#" title="TikTok" aria-label="TikTok"
                   class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-brand-600 transition"><i class="fab fa-tiktok"></i></a>
                <a href="#" title="YouTube" aria-label="YouTube"
                   class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-brand-600 transition"><i class="fab fa-youtube"></i></a>
            </div>
            <div class="text-sm text-gray-400">
                <p>&copy; {{ date('Y') }} Coleah Bandeya & Fils. Tous droits réservés. Conakry, Guinée.</p>
                <p class="mt-2">Chez Coleah Bandeya & Fils, au-delà de la qualité des autres.</p>
            </div>
        </div>
    </div>
</footer>
