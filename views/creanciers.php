<?php
include('../connexion/connexion.php');

try {
    $stmt = $connexion->query("SELECT matricule, nom, postnom, prenom, telephone, photo, statut, date_enregistrement FROM creancier ORDER BY date_enregistrement DESC");
    $creanciers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de récupération des créanciers : " . $e->getMessage());
}

// --- 2. GESTION DES MESSAGES DE SESSION (AJOUT/MODIF/SUPPR) ---
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? 'success';
unset($_SESSION['message']);
unset($_SESSION['message_type']); // Nettoyer après affichage
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Créanciers</title>
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
            max-width: 95%;
            width: 550px;
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

        /* Styles responsifs améliorés */
        @media (max-width: 768px) {
            .flex.h-screen {
                flex-direction: column;
            }
            
            aside.w-64 {
                width: 100%;
                height: auto;
                position: fixed;
                top: 0;
                left: 0;
                z-index: 40;
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }
            
            aside.w-64.mobile-open {
                transform: translateX(0);
            }
            
            .mobile-menu-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 30;
            }
            
            .mobile-menu-overlay.active {
                display: block;
            }
            
            .flex-1.flex.flex-col {
                margin-left: 0;
                width: 100%;
            }
            
            .mobile-menu-btn {
                display: block !important;
            }
            
            header.flex.items-center.justify-between {
                padding-left: 4rem;
                position: relative;
            }
            
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            table.min-w-full {
                min-width: 700px;
            }
            
            .modal-content {
                width: 95%;
                margin: 1rem;
            }
            
            .search-bar-mobile {
                flex-direction: column;
                gap: 1rem;
            }
            
            .search-bar-mobile .w-full {
                width: 100%;
            }
            
            .action-buttons-mobile {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
                align-items: center;
            }
            
            .form-grid-mobile {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }
            
            .btn-mobile-full {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 640px) {
            main.p-6 {
                padding: 1rem;
            }
            
            .text-2xl {
                font-size: 1.5rem;
            }
            
            .text-xl {
                font-size: 1.25rem;
            }
            
            header.flex.items-center.justify-between {
                padding: 0.75rem 1rem 0.75rem 4rem;
            }
            
            .table-cell-mobile {
                padding: 0.5rem 0.25rem;
                font-size: 0.875rem;
            }
            
            .action-buttons-mobile button {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
            
            .photo-preview-mobile {
                margin: 0 auto;
            }
        }

        @media (min-width: 769px) {
            .mobile-menu-btn {
                display: none !important;
            }
            
            .mobile-menu-overlay {
                display: none !important;
            }
        }

        /* Amélioration de l'affichage des boutons d'action */
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            justify-content: center;
        }
        
        .action-buttons button {
            white-space: nowrap;
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
        }
        
        /* Amélioration du header mobile */
        .header-mobile {
            position: sticky;
            top: 0;
            z-index: 20;
            background: white;
        }
        
        /* Amélioration du footer */
        .footer-mobile {
            padding: 1rem;
        }
        
        /* Styles pour les cartes */
        .card-hover {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>

<body class="bg-gray-100 text-gray-900 font-sans">
    <!-- Overlay pour le menu mobile -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    <div class="flex h-screen">
        <aside class="w-64 bg-red-900 text-white flex flex-col transition-transform duration-300 fixed h-full z-40" id="sidebar">
            <div class="p-6 text-center text-2xl font-bold border-b border-red-700 flex justify-between items-center">
                <span>GR_Shop</span>
                <button class="mobile-menu-btn hidden text-2xl md:hidden" id="closeSidebar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto">
                <ul class="p-4 space-y-2">
                    <li class="uppercase text-xs text-gray-400">Menus</li>
                    <li>
                        <a href="home.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            <i class="bi bi-house-door-fill mr-2"></i> Tableau de bord
                        </a>
                    </li>
                    <li>
                        <a href="produits.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            <i class="bi bi-box-seam-fill mr-2"></i> Produits
                        </a>
                    </li>
                    <li>
                        <a href="categories.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            <i class="bi bi-tags-fill mr-2"></i> Catégories
                        </a>
                    </li>
                    
                    <li>
                        <a href="creances.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            <i class="bi bi-cash-stack mr-2"></i> Créances
                        </a>
                    </li>
                    <li>
                        <a href="creanciers.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-700 bg-red-700 transition-colors">
                            <i class="bi bi-people-fill mr-2"></i> Créanciers
                        </a>
                    </li>
                    
                    <li>
                        <a href="ventes.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            <i class="bi bi-bar-chart-fill mr-2"></i> Ventes
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col ml-0 md:ml-64 transition-all duration-300">
            <header class="flex items-center justify-between bg-white shadow px-6 py-3 header-mobile">
                <button class="mobile-menu-btn hidden text-xl mr-4 md:hidden" id="openSidebar">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="text-xl font-bold">Créanciers</h1>
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
                <h2 class="text-2xl font-semibold mb-4">Gestion des Créanciers (Revendeurs)</h2>

                <?php if ($message): ?>
                    <div class="p-4 rounded-lg mb-4 text-white <?= ($message_type === 'success') ? 'bg-green-500' : 'bg-red-500' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 sm:space-x-4 search-bar-mobile">
                    <div class="relative w-full flex-1">
                        <input type="text" id="searchInput" placeholder="Rechercher par nom ou matricule..." class="form-control pl-10">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <button id="openAddModalBtn" class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-lg shadow-md transition-all hover:scale-105 whitespace-nowrap w-full sm:w-auto flex items-center justify-center btn-mobile-full">
                        <i class="bi bi-person-plus-fill mr-2"></i> Ajouter un Créancier
                    </button>
                </div>

                <div class="overflow-x-auto mt-4 table-container">
                    <table class="min-w-full bg-white shadow rounded-lg">
                        <thead class="bg-red-600 text-white">
                            <tr>
                                <th class="px-4 py-2 text-left table-cell-mobile">Matricule</th>
                                <th class="px-4 py-2 text-left table-cell-mobile">Noms Complet</th>
                                <th class="px-4 py-2 text-center table-cell-mobile">Photo</th>
                                <th class="px-4 py-2 text-center table-cell-mobile">Téléphone</th>
                                <th class="px-4 py-2 text-center table-cell-mobile">Statut</th>
                                <th class="px-4 py-2 text-center table-cell-mobile">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="creanciersTableBody">
                            <?php 
                            $default_photo_placeholder = 'https://placehold.co/50x50/FEE2E2/B91C1C?text=C';
                            $img_dir = '../img/'; // Assurez-vous que ce chemin est correct
                            ?>
                            <?php if (empty($creanciers)): ?>
                                <tr>
                                    <td colspan="6" class="py-3 px-6 text-center text-gray-500">Aucun créancier n'a été trouvé.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($creanciers as $index => $creancier): ?>
                                    <tr class="border-b hover:bg-red-50 transition duration-150">
                                        <td class="px-4 py-2 font-medium table-cell-mobile"><?= htmlspecialchars($creancier['matricule']) ?></td>
                                        <td class="px-4 py-2 table-cell-mobile">
                                            <div class="font-medium"><?= htmlspecialchars($creancier['nom']) ?></div>
                                            <div class="text-sm text-gray-600">
                                                <?= htmlspecialchars($creancier['postnom']) . ' ' . htmlspecialchars($creancier['prenom']) ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <?php 
                                            // Utiliser le chemin réel si la photo existe, sinon le placeholder
                                            $photo_src = (!empty($creancier['photo']) && file_exists($img_dir . $creancier['photo'])) 
                                                ? $img_dir . htmlspecialchars($creancier['photo']) 
                                                : $default_photo_placeholder; 
                                            ?>
                                            <img src="<?= $photo_src ?>" alt="Photo du créancier" class="w-10 h-10 object-cover rounded-full mx-auto">
                                        </td>
                                        <td class="px-4 py-2 text-center table-cell-mobile">
                                            <?= htmlspecialchars($creancier['telephone']) ?>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <?php
                                            $statut = htmlspecialchars($creancier['statut']);
                                            $badge_class = ($statut === 'Actif') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                            ?>
                                            <span class="py-1 px-3 rounded-full text-xs font-bold <?= $badge_class ?>">
                                                <?= $statut ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="action-buttons action-buttons-mobile">
                                                <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm edit-btn transition-all hover:scale-105 flex items-center"
                                                    data-matricule="<?= htmlspecialchars($creancier['matricule']) ?>"
                                                    data-nom="<?= htmlspecialchars($creancier['nom']) ?>"
                                                    data-postnom="<?= htmlspecialchars($creancier['postnom']) ?>"
                                                    data-prenom="<?= htmlspecialchars($creancier['prenom']) ?>"
                                                    data-telephone="<?= htmlspecialchars($creancier['telephone']) ?>"
                                                    data-statut="<?= htmlspecialchars($creancier['statut']) ?>"
                                                    data-photo="<?= htmlspecialchars($creancier['photo'] ?? '') ?>"> 
                                                    <i class="bi bi-pencil-square mr-1"></i> Modifier
                                                </button>
                                                <button type="button" class="bg-gray-400 hover:bg-gray-500 text-white px-3 py-1 rounded text-sm delete-btn transition-all hover:scale-105 flex items-center"
                                                    data-matricule="<?= htmlspecialchars($creancier['matricule']) ?>"
                                                    data-nomcomplet="<?= htmlspecialchars($creancier['nom']) . ' ' . htmlspecialchars($creancier['postnom']) ?>">
                                                    <i class="bi bi-trash-fill mr-1"></i> Supprimer
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-col sm:flex-row justify-between items-center">
                    <span id="resultsCount" class="text-sm text-gray-600 mb-2 sm:mb-0">
                        Affichage de <?= count($creanciers) ?> résultat(s).
                    </span>
                </div>
            </main>

            <footer class="bg-white shadow px-6 py-3 text-sm flex flex-col md:flex-row justify-between items-center footer-mobile">
                <p>2024 &copy; GR_Shop</p>
                <p class="mt-2 md:mt-0">Crafted with <span class="text-red-500"><i class="bi bi-heart-fill"></i></span> by <a href="#" class="text-red-600">Glad</a></p>
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
                    <i class="bi bi-person-badge-fill text-2xl mr-2"></i>
                    <h4 id="addEditModalTitle" class="text-center text-xl font-bold">Ajouter un nouveau Créancier</h4>
                </div>
                <form id="creancierForm" action="../traitement/creanciers-post.php" method="POST" class="p-3 rounded-b-lg grid grid-cols-2 gap-4 form-grid-mobile" enctype="multipart/form-data">
                    <input type="hidden" name="old_matricule" id="oldMatricule">
                    <input type="hidden" name="current_photo_name" id="currentPhotoPath"> 
                    
                    <div class="col-span-2" id="matriculeFieldGroup">
                        <label for="matricule" class="block mb-2">Matricule</label>
                        <input type="text" name="matricule" id="matricule" class="form-control bg-gray-100" readonly>
                        <span id="matriculeInfo" class="text-sm text-gray-500 mt-1 block">
                            Un Matricule sera attribuer automatiquement (Ex: G_Shop001).
                        </span>
                    </div>
                    
                    <div class="col-span-2 md:col-span-1">
                        <label for="nom" class="block mb-2">Nom <span class="text-danger">*</span></label>
                        <input required type="text" name="nom" id="nom" class="form-control" placeholder="Nom de famille">
                    </div>
                    <div class="col-span-2 md:col-span-1">
                        <label for="postnom" class="block mb-2">Postnom</label>
                        <input type="text" name="postnom" id="postnom" class="form-control" placeholder="Postnom">
                    </div>
                    <div class="col-span-2 md:col-span-1">
                        <label for="prenom" class="block mb-2">Prénom <span class="text-danger">*</span></label>
                        <input required type="text" name="prenom" id="prenom" class="form-control" placeholder="Prénom">
                    </div>

                    <div class="col-span-2 md:col-span-1">
                        <label for="telephone" class="block mb-2">Téléphone <span class="text-danger">*</span></label>
                        <input required type="text" name="telephone" id="telephone" class="form-control" placeholder="+243 8X XXX XX XX">
                    </div>
                    <div class="col-span-2 md:col-span-1">
                        <label for="statut" class="block mb-2">Statut <span class="text-danger">*</span></label>
                        <select required name="statut" id="statut" class="form-control">
                            <option value="Actif">Actif</option>
                            <option value="Bloqué">Bloqué</option>
                        </select>
                    </div>

                    <div class="col-span-2">
                        <label for="photo" class="block mb-2">Photo du Créancier</label>
                        <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                        
                        <div class="mt-3 border rounded-lg p-2 flex justify-center items-center h-32 w-32 bg-gray-100 mx-auto photo-preview-mobile">
                            <img id="photoPreview" src="<?= $default_photo_placeholder ?>" alt="Prévisualisation" class="max-h-full max-w-full object-contain rounded-full">
                        </div>
                    </div>
                    
                    <div class="col-span-2 pt-4">
                        <input type="submit" class="btn btn-success w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300 cursor-pointer" name="Valider" id="submitBtn" value="Enregistrer">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay">
        <div class="bg-white p-6 rounded-lg shadow-xl modal-content relative text-center" style="max-width: 95%; width: 400px;">
            <button id="closeDeleteModalBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-xl transition-all hover:rotate-90">
                &times;
            </button>
            <i class="bi bi-exclamation-triangle-fill text-yellow-500 text-5xl mb-4"></i>
            <h4 class="text-xl font-bold mb-2">Confirmer la suppression</h4>
            <p class="mb-4">Êtes-vous sûr de vouloir supprimer le créancier <strong id="creancierNameToDelete"></strong> (Matricule: <span id="matriculeToDelete"></span>) ?</p>
            <div class="flex justify-center space-x-4 flex-wrap gap-2">
                <button id="confirmDeleteBtn" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition-all hover:scale-105 flex items-center">
                    <i class="bi bi-trash-fill mr-2"></i> Supprimer
                </button>
                <button id="cancelDeleteBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md transition-all hover:scale-105 flex items-center">
                    <i class="bi bi-x-circle mr-2"></i> Annuler
                </button>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Gestion du menu mobile
            const sidebar = document.getElementById('sidebar');
            const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
            const openSidebarBtn = document.getElementById('openSidebar');
            const closeSidebarBtn = document.getElementById('closeSidebar');

            if (openSidebarBtn) {
                openSidebarBtn.addEventListener('click', function() {
                    sidebar.classList.add('mobile-open');
                    mobileMenuOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            }

            if (closeSidebarBtn) {
                closeSidebarBtn.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-open');
                    mobileMenuOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }

            mobileMenuOverlay.addEventListener('click', function() {
                sidebar.classList.remove('mobile-open');
                mobileMenuOverlay.classList.remove('active');
                document.body.style.overflow = '';
            });

            // Éléments du DOM généraux
            const addEditModal = document.getElementById('addEditModal');
            const deleteModal = document.getElementById('deleteModal');
            const creancierForm = document.getElementById('creancierForm');
            const addEditModalTitle = document.getElementById('addEditModalTitle');
            const submitBtn = document.getElementById('submitBtn');
            const defaultPlaceholder = "<?= $default_photo_placeholder ?>";
            const imgDir = "<?= $img_dir ?>";

            // Éléments spécifiques au Matricule
            const openAddModalBtn = document.getElementById('openAddModalBtn');
            const matriculeInput = document.getElementById('matricule');
            const oldMatriculeInput = document.getElementById('oldMatricule');
            const matriculeInfo = document.getElementById('matriculeInfo'); 

            // Champs du formulaire
            const nomInput = document.getElementById('nom');
            const postnomInput = document.getElementById('postnom');
            const prenomInput = document.getElementById('prenom');
            const telephoneInput = document.getElementById('telephone');
            const statutInput = document.getElementById('statut');

            // Modale de suppression
            const creancierNameToDelete = document.getElementById('creancierNameToDelete');
            const matriculeToDelete = document.getElementById('matriculeToDelete');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

            // Gestion Photo
            const photoInput = document.getElementById('photo');
            const photoPreview = document.getElementById('photoPreview');
            const currentPhotoPathInput = document.getElementById('currentPhotoPath');

            // Fonctions de modales
            function openModal(modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }

            function closeModal(modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            }
            
            // Gestion de la prévisualisation du fichier image
            photoInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        photoPreview.src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                } else {
                    const current_name = currentPhotoPathInput.value;
                    const fallback_src = (current_name && current_name !== '') 
                                            ? `${imgDir}${current_name}` 
                                            : defaultPlaceholder;
                    photoPreview.src = fallback_src;
                }
            });

            // Bouton AJOUTER (Configuration pour la génération automatique)
            openAddModalBtn.addEventListener('click', () => {
                creancierForm.reset();
                creancierForm.action = "../traitement/creanciers-post.php";
                addEditModalTitle.textContent = "Ajouter un nouveau Créancier";
                submitBtn.value = "Enregistrer";
                
                // Mettre le champ matricule en mode caché/info lors de l'AJOUT
                matriculeInput.value = ""; 
                matriculeInput.classList.add('hidden'); 
                matriculeInfo.classList.remove('hidden'); // Afficher le message d'info

                matriculeInput.removeAttribute('required'); // IMPORTANT: Le champ n'est pas requis
                oldMatriculeInput.value = "";
                
                // Réinitialisation photo
                currentPhotoPathInput.value = ""; 
                photoPreview.src = defaultPlaceholder;
                if (photoInput) photoInput.value = null; 

                openModal(addEditModal);
            });

            // Fermeture des modales
            document.getElementById('closeAddEditModalBtn').addEventListener('click', () => closeModal(addEditModal));
            document.getElementById('closeDeleteModalBtn').addEventListener('click', () => closeModal(deleteModal));
            document.getElementById('cancelDeleteBtn').addEventListener('click', () => closeModal(deleteModal));

            // Fermer les modales en cliquant à l'extérieur
            [addEditModal, deleteModal].forEach(modal => {
                if (modal) {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) {
                            closeModal(modal);
                        }
                    });
                }
            });

            // Clics sur les boutons (Édition/Suppression)
            document.querySelector('#creanciersTableBody').addEventListener('click', (event) => {
                const target = event.target;
                
                // --- BOUTON ÉDITION ---
                if (target.closest('.edit-btn')) {
                    const btn = target.closest('.edit-btn');
                    
                    // Récupération des données
                    const matricule = btn.getAttribute('data-matricule');
                    const nom = btn.getAttribute('data-nom');
                    const postnom = btn.getAttribute('data-postnom');
                    const prenom = btn.getAttribute('data-prenom');
                    const telephone = btn.getAttribute('data-telephone');
                    const statut = btn.getAttribute('data-statut');
                    const photo_name = btn.getAttribute('data-photo'); 

                    // Construction du chemin complet de la photo pour la prévisualisation
                    const full_photo_path = (photo_name && photo_name !== '') 
                                            ? `${imgDir}${photo_name}` 
                                            : defaultPlaceholder;


                    // 1. Remplissage des champs de formulaire
                    creancierForm.action = "../traitement/creanciers-post.php";
                    
                    // Mettre le champ matricule en mode lecture seule visible lors de l'ÉDITION
                    matriculeInput.value = matricule;
                    matriculeInput.classList.remove('hidden'); // Afficher
                    matriculeInfo.classList.add('hidden'); // Masquer le message d'info

                    matriculeInput.setAttribute('readonly', true); // Empêche la modification du matricule
                    matriculeInput.setAttribute('required', true); // Peut être requis pour l'édition mais n'est pas envoyé comme champ modifiable
                    oldMatriculeInput.value = matricule; // Stocke l'ancien matricule pour la mise à jour

                    nomInput.value = nom;
                    postnomInput.value = postnom;
                    prenomInput.value = prenom;
                    telephoneInput.value = telephone;
                    statutInput.value = statut; 
                    
                    // 2. Gestion des champs Photo pour l'édition
                    currentPhotoPathInput.value = photo_name; 
                    photoPreview.src = full_photo_path; 
                    if (photoInput) photoInput.value = null; 

                    // 3. Mise à jour des textes de la modale
                    addEditModalTitle.textContent = `Modifier Créancier (${matricule})`;
                    submitBtn.value = "Modifier";
                    openModal(addEditModal);
                }

                // --- BOUTON SUPPRESSION ---
                if (target.closest('.delete-btn')) {
                    const btn = target.closest('.delete-btn');
                    const matricule = btn.getAttribute('data-matricule');
                    const nomComplet = btn.getAttribute('data-nomcomplet');

                    creancierNameToDelete.textContent = nomComplet;
                    matriculeToDelete.textContent = matricule;
                    confirmDeleteBtn.setAttribute('data-matricule-to-delete', matricule);
                    openModal(deleteModal);
                }
            });

            // Confirmation de suppression
            confirmDeleteBtn.addEventListener('click', () => {
                const matriculeToDelete = confirmDeleteBtn.getAttribute('data-matricule-to-delete');
                const deleteForm = document.createElement('form');
                deleteForm.method = 'POST';
                deleteForm.action = '../traitement/creanciers-post.php';

                const matriculeInputHidden = document.createElement('input');
                matriculeInputHidden.type = 'hidden';
                matriculeInputHidden.name = 'delete_matricule';
                matriculeInputHidden.value = matriculeToDelete;
                deleteForm.appendChild(matriculeInputHidden);

                document.body.appendChild(deleteForm);
                deleteForm.submit();
            });
            
            // Logique de recherche (simplifiée)
            document.getElementById('searchInput').addEventListener('keyup', function() {
                const filter = this.value.toUpperCase();
                const rows = document.getElementById('creanciersTableBody').getElementsByTagName('tr');
                let count = 0;

                for (let i = 0; i < rows.length; i++) {
                    let matriculeTd = rows[i].getElementsByTagName('td')[0]; // Matricule
                    let nomsCompletTd = rows[i].getElementsByTagName('td')[1]; // Noms Complet
                    let isResultRow = false; // Par défaut, on ne tient pas compte des lignes d'absence de résultat

                    // Vérifie si la ligne est la ligne "Aucun créancier..."
                    if (rows[i].querySelector('td[colspan="6"]')) {
                        rows[i].style.display = "none";
                        continue;
                    }

                    // Vérifie les colonnes Matricule et Noms Complets
                    if (matriculeTd && matriculeTd.textContent.toUpperCase().indexOf(filter) > -1) {
                        isResultRow = true;
                    } else if (nomsCompletTd && nomsCompletTd.textContent.toUpperCase().indexOf(filter) > -1) {
                        isResultRow = true;
                    }

                    if (isResultRow) {
                        rows[i].style.display = "";
                        count++;
                    } else {
                        rows[i].style.display = "none";
                    }
                }
                
                // Mise à jour du compteur
                document.getElementById('resultsCount').textContent = `Affichage de ${count} résultat(s).`;
                
            });

            // Touche Échap pour fermer les modales
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (!addEditModal.classList.contains('hidden')) closeModal(addEditModal);
                    if (!deleteModal.classList.contains('hidden')) closeModal(deleteModal);
                }
            });
        });
    </script>
</body>
</html>