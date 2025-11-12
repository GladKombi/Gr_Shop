<?php
// Inclusion du fichier de connexion à la base de données
include('../connexion/connexion.php');

// Assurez-vous que ce fichier existe et contient la logique pour charger $products et $categories
require_once('../select/select-produits.php'); 
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Produits</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(2px);
        }

        .modal-content {
            animation: slideIn 0.3s ease-out;
            max-width: 90%;
            width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 1rem;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 0.5rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 0.5rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control:focus {
            color: #495057;
            background-color: #fff;
            border-color: #f07e86;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(255, 0, 0, 0.25);
        }

        .text-danger {
            color: #dc3545;
        }
    </style>
</head>

<body class="bg-gray-100 text-gray-900 font-sans">
    <div class="flex h-screen">

        <?php include_once('aside.php'); ?>

        <div class="flex-1 flex flex-col">

            <header class="flex items-center justify-between bg-white shadow px-6 py-3">
                <h1 class="text-xl font-bold">Produits</h1>
                <div class="flex items-center space-x-4">
                    <button class="relative">
                        <i class="bi bi-bell text-xl"></i>
                        <span class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                    <div class="flex items-center space-x-2">
                        <img src="https://placehold.co/128x128/E5E7EB/6B7280?text=A" class="w-8 h-8 rounded-full" alt="Avatar">
                        <span class="hidden md:block">User</span>
                    </div>
                </div>
            </header>

            <main class="p-6 overflow-y-auto flex-1">
                <h2 class="text-2xl font-semibold mb-4">Gestion des produits</h2>

                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <div class="relative w-full flex-1">
                        <input type="text" id="searchInput" placeholder="Rechercher un produit..." class="form-control pl-10">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <button id="openAddModalBtn" class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-lg shadow-md transition-transform transform hover:scale-105 whitespace-nowrap w-full sm:w-auto">
                        Ajouter un Produit
                    </button>
                </div>

                <div class="overflow-x-auto mt-4">
                    <table class="min-w-full bg-white shadow rounded-lg">
                        <thead class="bg-red-600 text-white">
                            <tr>
                                <th class="px-4 py-2">N°</th>
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2">Photo</th> <th class="px-4 py-2">Nom</th>
                                <th class="px-4 py-2">Catégorie</th>
                                <th class="px-4 py-2">Prix</th>
                                <th class="px-4 py-2">Stock</th>
                                <th class="px-4 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <?php 
                            // Définir un chemin par défaut si le champ 'photo' est vide
                            $default_photo = 'https://placehold.co/50x50/CCCCCC/000000?text=N/A';
                            ?>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="8" class="py-3 px-6 text-center text-gray-500">Aucun produit n'a été trouvé.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $index => $product): ?>
                                    <tr class="border-b">
                                        <td class="px-4 py-2"><?= $index + 1 ?></td>
                                        <td class="px-4 py-2"><?= htmlspecialchars($product['date']) ?></td>
                                        <td class="px-4 py-2">
                                            <?php 
                                            // Utilisation du chemin relatif ici pour l'affichage dans le tableau
                                            $photo_src = !empty($product['photo']) ? '../img/' . htmlspecialchars($product['photo']) : $default_photo; 
                                            ?>
                                            <img src="<?= $photo_src ?>" alt="Photo du produit" class="w-12 h-12 object-cover rounded mx-auto">
                                        </td>
                                        <td class="px-4 py-2"><?= htmlspecialchars($product['nom']) ?></td>
                                        <td class="px-4 py-2"><?= htmlspecialchars($product['description']) ?></td>
                                        <td class="px-4 py-2"><?= htmlspecialchars($product['prix']) ?></td>
                                        <td class="px-4 py-2">
                                            <?php
                                            $stock = htmlspecialchars($product['quantite_en_stock']);
                                            if ($stock < 10): 
                                            ?>
                                                <span class="bg-yellow-200 text-yellow-800 py-1 px-3 rounded-full text-xs font-bold">
                                                    <?= $stock ?>
                                                </span>
                                            <?php else: ?>
                                                <?= $stock ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-2 flex space-x-2">
                                            <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm edit-btn"
                                                data-id="<?= htmlspecialchars($product['id']) ?>"
                                                data-nom="<?= htmlspecialchars($product['nom']) ?>"
                                                data-categorie-id="<?= htmlspecialchars($product['categorie']) ?>" 
                                                data-prix="<?= htmlspecialchars($product['prix']) ?>"
                                                data-quantite-en-stock="<?= htmlspecialchars($product['quantite_en_stock']) ?>"
                                                data-photo="<?= htmlspecialchars($product['photo'] ?? '') ?>"> <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm delete-btn"
                                                data-id="<?= htmlspecialchars($product['id']) ?>"
                                                data-nom="<?= htmlspecialchars($product['nom']) ?>">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-col sm:flex-row justify-between items-center">
                    <span id="resultsCount" class="text-sm text-gray-600 mb-2 sm:mb-0"></span>
                    <div id="paginationContainer" class="flex justify-center items-center space-x-2">
                    </div>
                </div>
            </main>

            <footer class="bg-white shadow px-6 py-3 text-sm flex justify-between">
                <p>2024 &copy; GR_Shop</p>
                <p>Crafted with <span class="text-red-500"><i class="bi bi-heart-fill"></i></span> by <a href="#" class="text-red-600">Glad</a></p>
            </footer>
        </div>
    </div>

    <div id="addEditModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay">
        <div class="bg-white p-6 rounded-lg shadow-xl modal-content relative">
            <button id="closeAddEditModalBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-xl transition-all hover:rotate-90">
                &times;
            </button>
            <div class="w-full">
                <div class="bg-gradient-to-r from-red-600 to-red-800 text-white py-4 px-6 mb-4 rounded-t-lg flex items-center justify-center">
                    <i class="bi bi-box-seam-fill text-2xl mr-2"></i>
                    <h4 id="addEditModalTitle" class="text-center text-xl font-bold">Ajouter un nouveau produit</h4>
                </div>
                <form id="productForm" action="../traitement/produits-post.php" method="POST" class="p-3 rounded-b-lg" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="idProduct">
                    <input type="hidden" name="current_photo_name" id="currentPhotoPath"> 
                    <div class="row">
                        <div class="col-12 p-3">
                            <label for="nom" class="block mb-2">Nom du produit <span class="text-danger">*</span></label>
                            <input required type="text" name="nom" id="nom" class="form-control" placeholder="Entrez le nom du produit">
                        </div>
                        <div class="col-12 p-3">
                            <label for="categorie" class="block mb-2">Catégorie <span class="text-danger">*</span></label>
                            <select required name="categorie" id="categorie" class="form-control">
                                <option value="">Sélectionnez une catégorie</option>
                                <?php 
                                // Assurez-vous que $categories est défini et est un tableau
                                if (isset($categories) && is_array($categories)): 
                                    foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['id']) ?>"><?= htmlspecialchars($category['description']) ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                        <div class="col-12 p-3">
                            <label for="quantite_en_stock" class="block mb-2">Quantité en stock <span class="text-danger">*</span></label>
                            <input required type="number" name="quantite_en_stock" id="quantite_en_stock" class="form-control" placeholder="Entrez la quantité en stock">
                        </div>
                        <div class="col-12 p-3">
                            <label for="prix" class="block mb-2">Prix <span class="text-danger">*</span></label>
                            <input required type="number" step="0.01" name="prix" id="prix" class="form-control" placeholder="EX: 99.99">
                        </div>

                        <div class="col-12 p-3">
                            <label for="photo" class="block mb-2">Photo du produit</label>
                            <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                            
                            <div class="mt-3 border rounded-lg p-2 flex justify-center items-center h-32 w-32 mx-auto bg-gray-100">
                                <img id="photoPreview" src="https://placehold.co/100x100/CCCCCC/000000?text=Photo" alt="Prévisualisation" class="max-h-full max-w-full object-contain rounded-lg">
                            </div>
                        </div>
                        
                        <div class="col-12 p-3">
                            <input type="submit" class="btn btn-success w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300" name="Valider" id="submitBtn" value="Enregistrer">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay">
        <div class="bg-white p-6 rounded-lg shadow-xl modal-content relative text-center" style="width: 400px;">
            <button id="closeDeleteModalBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-xl transition-all hover:rotate-90">
                &times;
            </button>
            <i class="bi bi-exclamation-triangle-fill text-yellow-500 text-5xl mb-4"></i>
            <h4 class="text-xl font-bold mb-2">Confirmer la suppression</h4>
            <p class="mb-4">Êtes-vous sûr de vouloir supprimer le produit <strong id="productNameToDelete"></strong> ?</p>
            <div class="flex justify-center space-x-4">
                <button id="confirmDeleteBtn" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition-transform transform hover:scale-105">
                    Supprimer
                </button>
                <button id="cancelDeleteBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md transition-transform transform hover:scale-105">
                    Annuler
                </button>
            </div>
        </div>
    </div>
    
    <?php include_once('message.php'); ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const openAddModalBtn = document.getElementById('openAddModalBtn');
            const addEditModal = document.getElementById('addEditModal');
            const deleteModal = document.getElementById('deleteModal');
            const closeAddEditModalBtn = document.getElementById('closeAddEditModalBtn');
            const closeDeleteModalBtn = document.getElementById('closeDeleteModalBtn');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            const productForm = document.getElementById('productForm');
            const addEditModalTitle = document.getElementById('addEditModalTitle');
            const submitBtn = document.getElementById('submitBtn');
            const idProductInput = document.getElementById('idProduct');
            const nomInput = document.getElementById('nom');
            const categorieInput = document.getElementById('categorie');
            const prixInput = document.getElementById('prix');
            const quantiteEnStockInput = document.getElementById('quantite_en_stock'); 
            const productNameToDelete = document.getElementById('productNameToDelete');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

            // ÉLÉMENTS PHOTO
            const photoInput = document.getElementById('photo');
            const photoPreview = document.getElementById('photoPreview');
            const currentPhotoPathInput = document.getElementById('currentPhotoPath'); // Contient le nom du fichier
            const defaultPlaceholder = "https://placehold.co/100x100/CCCCCC/000000?text=Photo";

            function openModal(modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal(modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            
            // Gère la prévisualisation du fichier image sélectionné
            photoInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        photoPreview.src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                } else {
                    // Si l'utilisateur annule la sélection, réinitialise à l'image précédente ou par défaut
                    // On utilise currentPhotoPathInput.value (le nom du fichier) pour reconstruire le chemin
                    const current_name = currentPhotoPathInput.value;
                    const fallback_src = (current_name && current_name !== defaultPlaceholder) 
                                            ? `../img/${current_name}` 
                                            : defaultPlaceholder;
                    photoPreview.src = fallback_src;
                }
            });

            openAddModalBtn.addEventListener('click', () => {
                productForm.reset();
                productForm.action = "../traitement/produits-post.php";
                addEditModalTitle.textContent = "Ajouter un nouveau produit";
                submitBtn.value = "Enregistrer";
                idProductInput.value = "";
                
                // Réinitialisation des champs photo pour l'ajout
                currentPhotoPathInput.value = ""; 
                photoPreview.src = defaultPlaceholder;
                if (photoInput) photoInput.value = null; 

                openModal(addEditModal);
            });

            closeAddEditModalBtn.addEventListener('click', () => closeModal(addEditModal));
            closeDeleteModalBtn.addEventListener('click', () => closeModal(deleteModal));
            cancelDeleteBtn.addEventListener('click', () => closeModal(deleteModal));

            document.querySelector('#productsTableBody').addEventListener('click', (event) => {
                const target = event.target;
                if (target.closest('.edit-btn')) {
                    const btn = target.closest('.edit-btn');
                    
                    // Récupération des données du produit
                    const id = btn.getAttribute('data-id');
                    const nom = btn.getAttribute('data-nom');
                    const categorieId = btn.getAttribute('data-categorie-id'); 
                    const prix = btn.getAttribute('data-prix');
                    const quantite_en_stock = btn.getAttribute('data-quantite-en-stock');
                    const photo_name = btn.getAttribute('data-photo'); 

                    // Construction du chemin complet de la photo pour la prévisualisation
                    const full_photo_path = (photo_name && photo_name !== '') 
                                            ? `../img/${photo_name}` 
                                            : defaultPlaceholder;


                    // 1. Remplissage des champs de formulaire
                    productForm.action = "../traitement/produits-post.php"; // Chemin de traitement unique
                    idProductInput.value = id;
                    nomInput.value = nom;
                    categorieInput.value = categorieId; // Sélectionne l'ID dans le select
                    prixInput.value = prix;
                    quantiteEnStockInput.value = quantite_en_stock;
                    
                    // 2. Gestion des champs Photo pour l'édition
                    currentPhotoPathInput.value = photo_name; // Stocke UNIQUEMENT le nom du fichier 
                    photoPreview.src = full_photo_path; // Affiche la photo existante
                    if (photoInput) photoInput.value = null; // Réinitialise le champ file 

                    // 3. Mise à jour des textes de la modale
                    addEditModalTitle.textContent = "Modifier le produit";
                    submitBtn.value = "Modifier";
                    openModal(addEditModal);
                }

                if (target.closest('.delete-btn')) {
                    const btn = target.closest('.delete-btn');
                    const id = btn.getAttribute('data-id');
                    const nom = btn.getAttribute('data-nom');

                    productNameToDelete.textContent = nom;
                    confirmDeleteBtn.setAttribute('data-id-to-delete', id);
                    openModal(deleteModal);
                }
            });

            confirmDeleteBtn.addEventListener('click', () => {
                const idToDelete = confirmDeleteBtn.getAttribute('data-id-to-delete');
                const deleteForm = document.createElement('form');
                deleteForm.method = 'POST';
                deleteForm.action = '../traitement/produits-post.php';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'delete_id';
                idInput.value = idToDelete;
                deleteForm.appendChild(idInput);

                document.body.appendChild(deleteForm);
                deleteForm.submit();
            });
            
            // Logique de recherche (simplifiée pour l'exemple)
            document.getElementById('searchInput').addEventListener('keyup', function() {
                const filter = this.value.toUpperCase();
                const rows = document.getElementById('productsTableBody').getElementsByTagName('tr');
                let count = 0;

                for (let i = 0; i < rows.length; i++) {
                    let td = rows[i].getElementsByTagName('td');
                    let showRow = false;

                    // Skip the empty message row
                    if (td.length < 8) continue; 

                    // Check Name (Index 3)
                    if (td[3]) {
                        if (td[3].textContent.toUpperCase().indexOf(filter) > -1) {
                            showRow = true;
                        }
                    }

                    if (showRow) {
                        rows[i].style.display = "";
                        count++;
                    } else {
                        rows[i].style.display = "none";
                    }
                }
                document.getElementById('resultsCount').textContent = `Affichage de ${count} résultat(s).`;
            });
            
            // Initialisation du compteur de résultats
            const initialRowCount = document.getElementById('productsTableBody').getElementsByTagName('tr').length;
            if (initialRowCount > 0) {
                 const realRowCount = <?php echo count($products); ?>;
                 document.getElementById('resultsCount').textContent = `Affichage de ${realRowCount} résultat(s).`;
            } else {
                document.getElementById('resultsCount').textContent = `Affichage de 0 résultat.`;
            }
        });
    </script>
</body>

</html>