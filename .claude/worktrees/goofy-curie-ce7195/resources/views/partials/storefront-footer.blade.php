<footer class="bg-gray-900 text-gray-300 py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <div>
                <div class="flex items-center space-x-2 mb-4">
                    <img src="{{ asset('images/customers/logo.jpg') }}" alt="SMH" class="h-8 w-auto object-contain">
                    <span class="font-bold text-white">SMH</span>
                </div>
                <p class="text-sm text-gray-400">Spécialiste en ameublement — salons, chambres, salles à manger, bureaux et décoration — basé à Conakry, Guinée.</p>
            </div>
            <div>
                <h4 class="font-bold text-white mb-4">Liens utiles</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('about') }}" class="text-gray-400 hover:text-white transition">À propos</a></li>
                    <li><a href="{{ route('products.index') }}" class="text-gray-400 hover:text-white transition">Produits</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Blog</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Recrutement</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-white mb-4">Aide</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('contact') }}" class="text-gray-400 hover:text-white transition">Contact</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">FAQ</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Politique de retour</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">Mentions légales</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-white mb-4">Suivez-nous</h4>
                <div class="flex space-x-4">
                    <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-white hover:bg-amber-600 transition"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-white hover:bg-amber-600 transition"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-white hover:bg-amber-600 transition"><i class="fab fa-pinterest"></i></a>
                    <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-white hover:bg-amber-600 transition"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-800 pt-8 text-center text-sm text-gray-400">
            <p>&copy; {{ date('Y') }} SMH. Tous droits réservés. Conakry, Guinée.</p>
            <p class="mt-2">Chez SMH, au-delà de la qualité des autres.</p>
        </div>
    </div>
</footer>
