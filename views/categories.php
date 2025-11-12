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
    </style>
</head>

<body class="bg-gray-100 text-gray-900 font-sans">
    <div class="flex h-screen">

        <?php include_once('aside.php'); ?>

        <div class="flex-1 flex flex-col">

            <header class="flex items-center justify-between bg-white shadow px-6 py-3">
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


                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <div class="relative w-full flex-1">
                        <input type="text" id="searchInput" placeholder="Rechercher une catégorie..." class="form-control pl-10">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <button id="openAddModalBtn" class="bg-red-700 hover:bg-red-900 text-white px-4 py-2 rounded-lg shadow-md transition-transform transform hover:scale-105 whitespace-nowrap w-full sm:w-auto">
                        Ajouter une Catégorie
                    </button>
                </div>

                <div class="overflow-x-auto mt-4">
                    <table class="min-w-full bg-white shadow rounded-lg">
                        <thead class="bg-red-600 text-white">
                            <tr>
                                <th class="px-4 py-2">N°</th>
                                <th class="px-4 py-2">Description</th>
                                <th class="px-4 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="categoriesTableBody">
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="3" class="py-3 px-6 text-center text-gray-500">Aucune catégorie n'a été trouvée.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $index => $category): ?>
                                    <tr class="border-b">
                                        <td class="px-4 py-2"><?= $index + 1 ?></td>
                                        <td class="px-4 py-2"><?= htmlspecialchars($category['description']) ?></td>
                                        <td class="px-4 py-2 flex space-x-2">
                                            <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm edit-btn"
                                                data-id="<?= htmlspecialchars($category['id']) ?>"
                                                data-description="<?= htmlspecialchars($category['description']) ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm delete-btn"
                                                data-id="<?= htmlspecialchars($category['id']) ?>"
                                                data-description="<?= htmlspecialchars($category['description']) ?>">
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
            <p class="mb-4">Êtes-vous sûr de vouloir supprimer la catégorie <strong id="categoryNameToDelete"></strong> ?</p>
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
                        row.className = 'border-b';
                        row.innerHTML = `
                            <td class="px-4 py-2">${start + index + 1}</td>
                            <td class="px-4 py-2">${category.description}</td>
                            <td class="px-4 py-2 flex space-x-2">
                                <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm edit-btn"
                                    data-id="${category.id}"
                                    data-description="${category.description}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm delete-btn"
                                    data-id="${category.id}"
                                    data-description="${category.description}">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
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
                        prevLink.className = 'px-3 py-1 bg-gray-300 rounded-lg hover:bg-gray-400';
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
                        pageLink.className = `px-3 py-1 rounded-lg ${i === currentPage ? 'bg-red-600 text-white' : 'bg-gray-200 hover:bg-gray-300'}`;
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
                        nextLink.className = 'px-3 py-1 bg-gray-300 rounded-lg hover:bg-gray-400';
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
        });

        // Les scripts de gestion des modales ont été déplacés ici
        document.addEventListener('DOMContentLoaded', () => {
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
            }

            function closeModal(modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
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
        });
    </script>
</body>

</html>