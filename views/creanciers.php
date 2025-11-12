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
        /* Styles CSS inchangés pour le contexte de ce script */
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
    </style>
</head>

<body class="bg-gray-100 text-gray-900 font-sans">
    <div class="flex h-screen">

        <div class="w-64 bg-gray-800 text-white p-4">
            <h2 class="text-2xl font-bold mb-8">GR_Shop Admin</h2>
            <nav class="space-y-2">
                <a href="#" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-red-600 transition duration-150">
                    <i class="bi bi-box-seam-fill"></i><span>Produits</span>
                </a>
                <a href="#" class="flex items-center space-x-2 p-2 rounded-lg bg-red-600 transition duration-150">
                    <i class="bi bi-person-badge-fill"></i><span>Créanciers</span>
                </a>
                <a href="#" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-red-600 transition duration-150">
                    <i class="bi bi-truck"></i><span>Crédits</span>
                </a>
            </nav>
        </div>


        <div class="flex-1 flex flex-col">

            <header class="flex items-center justify-between bg-white shadow px-6 py-3">
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

                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <div class="relative w-full flex-1">
                        <input type="text" id="searchInput" placeholder="Rechercher par nom ou matricule..." class="form-control pl-10">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <button id="openAddModalBtn" class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-lg shadow-md transition-transform transform hover:scale-105 whitespace-nowrap w-full sm:w-auto">
                        Ajouter un Créancier
                    </button>
                </div>

                <div class="overflow-x-auto mt-4">
                    <table class="min-w-full bg-white shadow rounded-lg">
                        <thead class="bg-red-600 text-white">
                            <tr>
                                <th class="px-4 py-2 text-left">Matricule</th>
                                <th class="px-4 py-2 text-left">Noms Complet</th>
                                <th class="px-4 py-2">Photo</th>
                                <th class="px-4 py-2">Téléphone</th>
                                <th class="px-4 py-2">Statut</th>
                                <th class="px-4 py-2">Actions</th>
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
                                        <td class="px-4 py-2 font-medium"><?= htmlspecialchars($creancier['matricule']) ?></td>
                                        <td class="px-4 py-2">
                                            <?= htmlspecialchars($creancier['nom']) . ' ' . htmlspecialchars($creancier['postnom']) . ' ' . htmlspecialchars($creancier['prenom']) ?>
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
                                        <td class="px-4 py-2"><?= htmlspecialchars($creancier['telephone']) ?></td>
                                        <td class="px-4 py-2 text-center">
                                            <?php
                                            $statut = htmlspecialchars($creancier['statut']);
                                            $badge_class = ($statut === 'Actif') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                            ?>
                                            <span class="py-1 px-3 rounded-full text-xs font-bold <?= $badge_class ?>">
                                                <?= $statut ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 flex space-x-2 justify-center">
                                            <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm edit-btn"
                                                data-matricule="<?= htmlspecialchars($creancier['matricule']) ?>"
                                                data-nom="<?= htmlspecialchars($creancier['nom']) ?>"
                                                data-postnom="<?= htmlspecialchars($creancier['postnom']) ?>"
                                                data-prenom="<?= htmlspecialchars($creancier['prenom']) ?>"
                                                data-telephone="<?= htmlspecialchars($creancier['telephone']) ?>"
                                                data-statut="<?= htmlspecialchars($creancier['statut']) ?>"
                                                data-photo="<?= htmlspecialchars($creancier['photo'] ?? '') ?>"> 
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" class="bg-gray-400 hover:bg-gray-500 text-white px-3 py-1 rounded text-sm delete-btn"
                                                data-matricule="<?= htmlspecialchars($creancier['matricule']) ?>"
                                                data-nomcomplet="<?= htmlspecialchars($creancier['nom']) . ' ' . htmlspecialchars($creancier['postnom']) ?>">
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
                    <i class="bi bi-person-badge-fill text-2xl mr-2"></i>
                    <h4 id="addEditModalTitle" class="text-center text-xl font-bold">Ajouter un nouveau Créancier</h4>
                </div>
                <form id="creancierForm" action="../traitement/creanciers-post.php" method="POST" class="p-3 rounded-b-lg grid grid-cols-2 gap-4" enctype="multipart/form-data">
                    <input type="hidden" name="old_matricule" id="oldMatricule">
                    <input type="hidden" name="current_photo_name" id="currentPhotoPath"> 
                    
                    <div class="col-span-2" id="matriculeFieldGroup">
                        <label for="matricule" class="block mb-2">Matricule</label>
                        <input type="text" name="matricule" id="matricule" class="form-control bg-gray-100" readonly>
                        <span id="matriculeInfo" class="text-sm text-gray-500 mt-1 block">
                            Un Matricule sera attribuer automatiquement (Ex: G_Shop001).
                        </span>
                    </div>
                    
                    <div class="col-span-1">
                        <label for="nom" class="block mb-2">Nom <span class="text-danger">*</span></label>
                        <input required type="text" name="nom" id="nom" class="form-control" placeholder="Nom de famille">
                    </div>
                    <div class="col-span-1">
                        <label for="postnom" class="block mb-2">Postnom</label>
                        <input type="text" name="postnom" id="postnom" class="form-control" placeholder="Postnom">
                    </div>
                    <div class="col-span-1">
                        <label for="prenom" class="block mb-2">Prénom <span class="text-danger">*</span></label>
                        <input required type="text" name="prenom" id="prenom" class="form-control" placeholder="Prénom">
                    </div>

                    <div class="col-span-1">
                        <label for="telephone" class="block mb-2">Téléphone <span class="text-danger">*</span></label>
                        <input required type="text" name="telephone" id="telephone" class="form-control" placeholder="+243 8X XXX XX XX">
                    </div>
                    <div class="col-span-1">
                        <label for="statut" class="block mb-2">Statut <span class="text-danger">*</span></label>
                        <select required name="statut" id="statut" class="form-control">
                            <option value="Actif">Actif</option>
                            <option value="Bloqué">Bloqué</option>
                        </select>
                    </div>

                    <div class="col-span-1">
                        <label for="photo" class="block mb-2">Photo du Créancier</label>
                        <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                        
                        <div class="mt-3 border rounded-lg p-2 flex justify-center items-center h-32 w-32 bg-gray-100">
                            <img id="photoPreview" src="<?= $default_photo_placeholder ?>" alt="Prévisualisation" class="max-h-full max-w-full object-contain rounded-full">
                        </div>
                    </div>
                    
                    <div class="col-span-2 pt-4">
                        <input type="submit" class="btn btn-success w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300" name="Valider" id="submitBtn" value="Enregistrer">
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
            <p class="mb-4">Êtes-vous sûr de vouloir supprimer le créancier <strong id="creancierNameToDelete"></strong> (Matricule: <span id="matriculeToDelete"></span>) ?</p>
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
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
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
            }

            function closeModal(modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
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
            
            // Initialisation du compteur de résultats
            const initialRowCount = <?php echo count($creanciers); ?>;
            document.getElementById('resultsCount').textContent = `Affichage de ${initialRowCount} résultat(s).`;
        });
    </script>
</body>
</html>