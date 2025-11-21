<?php
// Inclusion du fichier de connexion à la base de données
include('../connexion/connexion.php');
require_once('../select/select-categorie.php');
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories de Produits</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

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
            border-color: #dc3545;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(129, 16, 1, 0.25);
        }

        .text-danger {
            color: #dc3545;
        }

        /* Styles personnalisés pour Toastify */
        .toastify {
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
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
                min-width: 500px;
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
            
            .pagination-mobile {
                flex-direction: column;
                gap: 1rem;
                align-items: center;
            }
            
            .pagination-mobile .flex {
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
            
            .btn-mobile-full {
                width: 100%;
                justify-content: center;
            }
            
            .table-cell-mobile {
                padding: 0.5rem 0.25rem;
                font-size: 0.875rem;
            }
            
            .action-buttons-mobile a,
            .action-buttons-mobile button {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
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
        
        /* Styles pour la pagination */
        .pagination-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        
        .pagination-btn:hover {
            transform: scale(1.05);
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
                        <a href="categories.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-700 bg-red-700 transition-colors">
                            <i class="bi bi-tags-fill mr-2"></i> Catégories
                        </a>
                    </li>
                    
                    <li>
                        <a href="creances.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            <i class="bi bi-cash-stack mr-2"></i> Créances
                        </a>
                    </li>
                    <li>
                        <a href="creanciers.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-700 transition-colors">
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
                <h1 class="text-xl font-bold">Catégories de Produits</h1>
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
                <h2 class="text-2xl font-semibold mb-4">Gestion des catégories</h2>

                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 sm:space-x-4 search-bar-mobile">
                    <div class="relative w-full flex-1">
                        <input type="text" id="searchInput" placeholder="Rechercher une catégorie..." class="form-control pl-10">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <button id="openAddModalBtn" class="bg-red-700 hover:bg-red-900 text-white px-4 py-2 rounded-lg shadow-md transition-all hover:scale-105 whitespace-nowrap w-full sm:w-auto flex items-center justify-center btn-mobile-full">
                        <i class="bi bi-plus-circle mr-2"></i> Ajouter une Catégorie
                    </button>
                </div>

                <div class="overflow-x-auto mt-4 table-container">
                    <table class="min-w-full bg-white shadow rounded-lg">
                        <thead class="bg-red-600 text-white">
                            <tr>
                                <th class="px-4 py-2 text-center">N°</th>
                                <th class="px-4 py-2 text-left">Description</th>
                                <th class="px-4 py-2 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="categoriesTableBody">
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="3" class="py-3 px-6 text-center text-gray-500">Aucune catégorie n'a été trouvée.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $index => $category): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-4 py-2 text-center table-cell-mobile"><?= $index + 1 ?></td>
                                        <td class="px-4 py-2 text-left table-cell-mobile font-medium"><?= htmlspecialchars($category['description']) ?></td>
                                        <td class="px-4 py-2 text-center">
                                            <div class="action-buttons action-buttons-mobile">
                                                <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm edit-btn transition-all hover:scale-105 flex items-center"
                                                    data-id="<?= htmlspecialchars($category['id']) ?>"
                                                    data-description="<?= htmlspecialchars($category['description']) ?>">
                                                    <i class="bi bi-pencil-square mr-1"></i> Modifier
                                                </button>
                                                <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm delete-btn transition-all hover:scale-105 flex items-center"
                                                    data-id="<?= htmlspecialchars($category['id']) ?>"
                                                    data-description="<?= htmlspecialchars($category['description']) ?>">
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
                <div class="mt-4 flex flex-col sm:flex-row justify-between items-center pagination-mobile">
                    <span id="resultsCount" class="text-sm text-gray-600 mb-2 sm:mb-0"></span>
                    <div id="paginationContainer" class="flex justify-center items-center space-x-2 flex-wrap gap-2"></div>
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
                    <i class="bi bi-tags-fill text-2xl mr-2"></i>
                    <h4 id="addEditModalTitle" class="text-center text-xl font-bold">Ajouter une nouvelle catégorie</h4>
                </div>
                <form id="categoryForm" action="../traitement/categorie-post.php" method="POST" class="p-3 rounded-b-lg">
                    <input type="hidden" name="id" id="idCategory">
                    <div class="row">
                        <div class="col-12 p-3">
                            <label for="description" class="block mb-2">Description <span class="text-danger">*</span></label>
                            <input required type="text" name="description" id="description" class="form-control" placeholder="Entrez une description pour la catégorie">
                        </div>
                        <div class="col-12 p-3">
                            <input type="submit" class="btn btn-success w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300 cursor-pointer" name="Valider" id="submitBtn" value="Enregistrer">
                        </div>
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
            <p class="mb-4">Êtes-vous sûr de vouloir supprimer la catégorie <strong id="categoryNameToDelete"></strong> ?</p>
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
    <?php include_once('message.php'); ?>

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

            // Données initiales des catégories
            const allCategories = <?= json_encode($categories) ?>;
            let filteredCategories = [...allCategories];
            let currentPage = 1;
            const resultsPerPage = 10;

            const categoriesTableBody = document.getElementById('categoriesTableBody');
            const searchInput = document.getElementById('searchInput');
            const paginationContainer = document.getElementById('paginationContainer');
            const resultsCountSpan = document.getElementById('resultsCount');

            // Fonctions de rendu
            function renderTable() {
                const start = (currentPage - 1) * resultsPerPage;
                const end = start + resultsPerPage;
                const categoriesToDisplay = filteredCategories.slice(start, end);

                categoriesTableBody.innerHTML = '';
                if (categoriesToDisplay.length === 0) {
                    categoriesTableBody.innerHTML = `<tr><td colspan="3" class="py-3 px-6 text-center text-gray-500">Aucune catégorie ne correspond à votre recherche.</td></tr>`;
                } else {
                    categoriesToDisplay.forEach((category, index) => {
                        const row = document.createElement('tr');
                        row.className = 'border-b hover:bg-gray-50';
                        row.innerHTML = `
                            <td class="px-4 py-2 text-center table-cell-mobile">${start + index + 1}</td>
                            <td class="px-4 py-2 text-left table-cell-mobile font-medium">${category.description}</td>
                            <td class="px-4 py-2 text-center">
                                <div class="action-buttons action-buttons-mobile">
                                    <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm edit-btn transition-all hover:scale-105 flex items-center"
                                        data-id="${category.id}"
                                        data-description="${category.description}">
                                        <i class="bi bi-pencil-square mr-1"></i> Modifier
                                    </button>
                                    <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm delete-btn transition-all hover:scale-105 flex items-center"
                                        data-id="${category.id}"
                                        data-description="${category.description}">
                                        <i class="bi bi-trash-fill mr-1"></i> Supprimer
                                    </button>
                                </div>
                            </td>
                        `;
                        categoriesTableBody.appendChild(row);
                    });
                }
                resultsCountSpan.textContent = `Affichage de ${filteredCategories.length} résultat${filteredCategories.length > 1 ? 's' : ''}`;
            }

            function renderPagination() {
                const totalPages = Math.ceil(filteredCategories.length / resultsPerPage);
                paginationContainer.innerHTML = '';

                if (totalPages > 1) {
                    if (currentPage > 1) {
                        const prevLink = document.createElement('a');
                        prevLink.href = '#';
                        prevLink.textContent = 'Précédent';
                        prevLink.className = 'px-3 py-1 bg-gray-300 rounded-lg hover:bg-gray-400 pagination-btn';
                        prevLink.addEventListener('click', (e) => {
                            e.preventDefault();
                            currentPage--;
                            renderTable();
                            renderPagination();
                        });
                        paginationContainer.appendChild(prevLink);
                    }

                    for (let i = 1; i <= totalPages; i++) {
                        const pageLink = document.createElement('a');
                        pageLink.href = '#';
                        pageLink.textContent = i;
                        pageLink.className = `px-3 py-1 rounded-lg pagination-btn ${i === currentPage ? 'bg-red-600 text-white' : 'bg-gray-200 hover:bg-gray-300'}`;
                        pageLink.addEventListener('click', (e) => {
                            e.preventDefault();
                            currentPage = i;
                            renderTable();
                            renderPagination();
                        });
                        paginationContainer.appendChild(pageLink);
                    }

                    if (currentPage < totalPages) {
                        const nextLink = document.createElement('a');
                        nextLink.href = '#';
                        nextLink.textContent = 'Suivant';
                        nextLink.className = 'px-3 py-1 bg-gray-300 rounded-lg hover:bg-gray-400 pagination-btn';
                        nextLink.addEventListener('click', (e) => {
                            e.preventDefault();
                            currentPage++;
                            renderTable();
                            renderPagination();
                        });
                        paginationContainer.appendChild(nextLink);
                    }
                }
            }

            // Gestionnaire d'événements pour la recherche
            searchInput.addEventListener('input', (event) => {
                const searchTerm = event.target.value.toLowerCase();
                filteredCategories = allCategories.filter(category =>
                    category.description.toLowerCase().includes(searchTerm)
                );
                currentPage = 1;
                renderTable();
                renderPagination();
            });

            // Appel initial pour afficher le tableau et la pagination
            renderTable();
            renderPagination();

            // Gestion des modales
            const openAddModalBtn = document.getElementById('openAddModalBtn');
            const addEditModal = document.getElementById('addEditModal');
            const deleteModal = document.getElementById('deleteModal');
            const closeAddEditModalBtn = document.getElementById('closeAddEditModalBtn');
            const closeDeleteModalBtn = document.getElementById('closeDeleteModalBtn');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            const categoryForm = document.getElementById('categoryForm');
            const addEditModalTitle = document.getElementById('addEditModalTitle');
            const submitBtn = document.getElementById('submitBtn');
            const idCategoryInput = document.getElementById('idCategory');
            const descriptionInput = document.getElementById('description');
            const categoryNameToDelete = document.getElementById('categoryNameToDelete');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

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

            openAddModalBtn.addEventListener('click', () => {
                categoryForm.reset();
                categoryForm.action = "../traitement/categorie-post.php";
                addEditModalTitle.textContent = "Ajouter une nouvelle catégorie";
                submitBtn.value = "Enregistrer";
                idCategoryInput.value = "";
                openModal(addEditModal);
            });

            closeAddEditModalBtn.addEventListener('click', () => closeModal(addEditModal));
            closeDeleteModalBtn.addEventListener('click', () => closeModal(deleteModal));
            cancelDeleteBtn.addEventListener('click', () => closeModal(deleteModal));

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

            document.querySelector('#categoriesTableBody').addEventListener('click', (event) => {
                const target = event.target;
                if (target.closest('.edit-btn')) {
                    const btn = target.closest('.edit-btn');
                    const id = btn.getAttribute('data-id');
                    const description = btn.getAttribute('data-description');

                    categoryForm.action = "../traitement/categorie-post.php";
                    idCategoryInput.value = id;
                    descriptionInput.value = description;
                    addEditModalTitle.textContent = "Modifier la catégorie";
                    submitBtn.value = "Modifier";
                    openModal(addEditModal);
                }

                if (target.closest('.delete-btn')) {
                    const btn = target.closest('.delete-btn');
                    const id = btn.getAttribute('data-id');
                    const description = btn.getAttribute('data-description');

                    categoryNameToDelete.textContent = description;
                    confirmDeleteBtn.setAttribute('data-id-to-delete', id);
                    openModal(deleteModal);
                }
            });

            confirmDeleteBtn.addEventListener('click', () => {
                const idToDelete = confirmDeleteBtn.getAttribute('data-id-to-delete');
                const deleteForm = document.createElement('form');
                deleteForm.method = 'POST';
                deleteForm.action = '../traitement/categorie-post.php';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'delete_id';
                idInput.value = idToDelete;
                deleteForm.appendChild(idInput);

                document.body.appendChild(deleteForm);
                deleteForm.submit();
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