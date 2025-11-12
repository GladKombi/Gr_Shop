<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GR_Shop - Bijoux, Montres & Accessoires de Parure</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .category-icon {
            font-size: 3rem; 
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">

    <header class="bg-white shadow-md py-4">
        <div class="container mx-auto flex justify-between items-center px-6">
            <h1 class="text-3xl font-extrabold text-red-600">GR_Shop</h1>
            <nav class="hidden md:flex items-center space-x-6">
                <a href="#" class="text-gray-600 hover:text-red-600 font-medium">Bijoux</a>
                <a href="#" class="text-gray-600 hover:text-red-600 font-medium">Montres</a>
                <a href="#" class="text-gray-600 hover:text-red-600 font-medium">Accessoires</a>
                <a href="#" class="text-gray-600 hover:text-red-600 font-medium">A Propos</a>
            </nav>
            <div class="flex items-center space-x-4">
                <a href="#" class="text-gray-600 hover:text-red-600 font-medium">
                    🛒 Panier
                </a>
                <a href="#" class="bg-red-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-700 transition duration-300">
                    Mon Compte
                </a>
            </div>
        </div>
    </header>

    <section class="bg-gray-200 py-12 border-b-4 border-red-600">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Trouvez votre parure idéale chez GR_Shop</h2>
            
            <div class="bg-white p-6 rounded-xl shadow-xl max-w-5xl mx-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <select class="p-3 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                        <option>Type de parure</option>
                        <option>Bague</option>
                        <option>Collier</option>
                        <option>Montre</option>
                        <option>Boucle d'oreille</option>
                    </select>
                    
                    <select class="p-3 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                        <option>Genre</option>
                        <option>Femme</option>
                        <option>Homme</option>
                        <option>Enfant</option>
                        <option>Unisexe</option>
                    </select>

                    <select class="p-3 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                        <option>Matériau</option>
                        <option>Or (Jaune/Blanc)</option>
                        <option>Argent 925</option>
                        <option>Acier Inoxydable</option>
                        <option>Cuir</option>
                    </select>

                    <button class="bg-red-600 text-white p-3 rounded-lg font-bold hover:bg-red-700 transition duration-300 transform hover:scale-105">
                        RECHERCHER
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-white">
        <div class="container mx-auto text-center px-6">
            <h3 class="text-4xl font-bold text-gray-800 mb-12">Naviguez par Catégorie Principale</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                
                <a href="#" class="p-6 bg-red-50 rounded-lg shadow-md hover:shadow-xl transition duration-300 transform hover:scale-105 border-b-4 border-red-500">
                    <div class="text-red-600 category-icon">💍</div> 
                    <p class="text-xl font-semibold text-gray-800">Bijoux</p>
                </a>
                
                <a href="#" class="p-6 bg-red-50 rounded-lg shadow-md hover:shadow-xl transition duration-300 transform hover:scale-105 border-b-4 border-red-500">
                    <div class="text-red-600 category-icon">⏱️</div>
                    <p class="text-xl font-semibold text-gray-800">Montres</p>
                </a>
                
                <a href="#" class="p-6 bg-red-50 rounded-lg shadow-md hover:shadow-xl transition duration-300 transform hover:scale-105 border-b-4 border-red-500">
                    <div class="text-red-600 category-icon">🤵</div> 
                    <p class="text-xl font-semibold text-gray-800">Parures Homme</p>
                </a>
                
                <a href="#" class="p-6 bg-red-50 rounded-lg shadow-md hover:shadow-xl transition duration-300 transform hover:scale-105 border-b-4 border-red-500">
                    <div class="text-red-600 category-icon">👩‍🦰</div> 
                    <p class="text-xl font-semibold text-gray-800">Parures Femme</p>
                </a>
            </div>
        </div>
    </section>

    <section class="py-16 bg-gray-100">
        <div class="container mx-auto px-6">
            <h3 class="text-4xl font-bold text-gray-800 mb-12 text-center">Nos Coups de Cœur du Moment</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-8">
                
                <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200 hover:shadow-2xl transition duration-300">
                    <div class="h-48 bg-gray-300 flex items-center justify-center text-gray-500">
                        [Image Bague Or]
                    </div>
                    <div class="p-4">
                        <h4 class="text-lg font-semibold text-gray-800 truncate">Bague Or Blanc "Éclat Céleste"</h4>
                        <p class="text-sm text-gray-500 mb-2">Bijoux | Femme</p>
                        <p class="text-2xl font-bold text-red-600 mb-3">499 €</p>
                        <a href="#" class="block text-center bg-red-500 text-white py-2 rounded-full hover:bg-red-600">
                            Voir le détail
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200 hover:shadow-2xl transition duration-300">
                    <div class="h-48 bg-gray-300 flex items-center justify-center text-gray-500">
                        [Image Montre Cuir]
                    </div>
                    <div class="p-4">
                        <h4 class="text-lg font-semibold text-gray-800 truncate">Montre Chrono Cuir Classique</h4>
                        <p class="text-sm text-gray-500 mb-2">Montres | Homme</p>
                        <p class="text-2xl font-bold text-red-600 mb-3">185 €</p>
                        <a href="#" class="block text-center bg-red-500 text-white py-2 rounded-full hover:bg-red-600">
                            Voir le détail
                        </a>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200 hover:shadow-2xl transition duration-300">
                    <div class="h-48 bg-gray-300 flex items-center justify-center text-gray-500">
                        [Image Collier]
                    </div>
                    <div class="p-4">
                        <h4 class="text-lg font-semibold text-gray-800 truncate">Collier Pendentif "Petite Étoile"</h4>
                        <p class="text-sm text-gray-500 mb-2">Bijoux | Enfant</p>
                        <p class="text-2xl font-bold text-red-600 mb-3">45 €</p>
                        <a href="#" class="block text-center bg-red-500 text-white py-2 rounded-full hover:bg-red-600">
                            Voir le détail
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200 hover:shadow-2xl transition duration-300">
                    <div class="h-48 bg-gray-300 flex items-center justify-center text-gray-500">
                        [Image Boutons Manchette]
                    </div>
                    <div class="p-4">
                        <h4 class="text-lg font-semibold text-gray-800 truncate">Boutons de Manchette Acier Brossé</h4>
                        <p class="text-sm text-gray-500 mb-2">Accessoires | Homme</p>
                        <p class="text-2xl font-bold text-red-600 mb-3">65 €</p>
                        <a href="#" class="block text-center bg-red-500 text-white py-2 rounded-full hover:bg-red-600">
                            Voir le détail
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="mt-12 text-center">
                <a href="#" class="bg-red-600 text-white px-8 py-4 rounded-lg text-lg font-bold hover:bg-red-700 transition duration-300 transform hover:scale-105 shadow-md">
                    Voir TOUTES les parures en stock
                </a>
            </div>
        </div>
    </section>

    <footer class="bg-gray-800 text-white py-8">
        <div class="container mx-auto grid grid-cols-1 md:grid-cols-4 gap-8 px-6">
            
            <div>
                <h5 class="text-xl font-bold mb-4 text-red-600">GR_Shop</h5>
                <p class="text-gray-400 text-sm">Votre source d'élégance pour bijoux, montres et accessoires de parure.</p>
            </div>
            
            <div>
                <h5 class="text-lg font-semibold mb-4">Informations</h5>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" class="text-gray-400 hover:text-white">A Propos</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Contactez-nous</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Livraison & Retours</a></li>
                </ul>
            </div>

            <div>
                <h5 class="text-lg font-semibold mb-4">Aide</h5>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" class="text-gray-400 hover:text-white">FAQ</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Politique de Confidentialité</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Conditions de Vente</a></li>
                </ul>
            </div>

            <div class="text-sm">
                <h5 class="text-lg font-semibold mb-4">Newsletter</h5>
                <p class="text-gray-400 mb-3">Recevez nos nouveautés et offres exclusives.</p>
                <input type="email" placeholder="Votre email" class="w-full p-2 rounded-lg text-gray-900 mb-2">
                <button class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">S'abonner</button>
            </div>
        </div>
        <div class="container mx-auto text-center mt-8 pt-4 border-t border-gray-700">
            <p class="text-gray-400 text-sm">&copy; 2023 GR_Shop. Tous droits réservés.</p>
        </div>
    </footer>

</body>
</html>