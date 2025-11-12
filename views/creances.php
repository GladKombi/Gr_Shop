<?php
// Inclusion du fichier de connexion à la base de données
include('../connexion/connexion.php');

// Définitions des variables pour les données affichées
$current_creance_id = null;
$creance_lines = [];
$products = []; // Liste des produits pour l'ajout d'une ligne de créance
$existing_creanciers = []; // Liste des créanciers existants pour Select2
$creancier_name = '...'; // Nom du créancier/client

// Récupère l'ID de la créance en cours stocké en session, s'il existe
if (isset($_SESSION['current_creance_id'])) {
    $current_creance_id = $_SESSION['current_creance_id'];
}

try {
    // 0. Récupérer la liste des créanciers existants (pour Select2)
    // MODIFICATION 1: Sélectionner MATRICULE, NOM et PRENOM
    $req_existing_creanciers = $connexion->prepare("SELECT matricule, nom, prenom FROM `creancier` ORDER BY nom ASC, prenom ASC");
    $req_existing_creanciers->execute();
    
    // MODIFICATION 2: Utiliser PDO::FETCH_ASSOC pour récupérer toutes les colonnes
    $existing_creanciers = $req_existing_creanciers->fetchAll(PDO::FETCH_ASSOC); 

    if (!empty($current_creance_id)) {
        // --- CAS 1: CRÉANCE EN COURS ---
        
        // 1. Récupère le nom du Créancier et la date d'échéance
        $req_creance_info = $connexion->prepare("SELECT creancier, echeance FROM `creance` WHERE id = :id");
        $req_creance_info->execute(['id' => $current_creance_id]);
        $creance_data = $req_creance_info->fetch(PDO::FETCH_ASSOC);
        if ($creance_data) {
            $creancier_name = htmlspecialchars($creance_data['creancier']);
            $echeance_date = htmlspecialchars(date('d/m/Y', strtotime($creance_data['echeance'])));
        }

        // 2. Récupère toutes les LIGNES DE CRÉANCE (les produits concernés)
        $req_lines = $connexion->prepare("SELECT lc.id, lc.quantite, lc.prix, lc.statut,p.nom as nom_produit FROM `ligne_creance` lc JOIN `produits` p ON lc.produit = p.id WHERE lc.statut = 0 AND lc.creance = :id ORDER BY lc.id DESC");
        $req_lines->execute(['id' => $current_creance_id]);
        $creance_lines = $req_lines->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Récupère tous les produits disponibles (en tenant compte du stock)
        $req_products = $connexion->prepare(" 
            SELECT p.id, p.nom, p.prix, p.quantite_en_stock AS stock_disponible
            FROM `produits` p 
            WHERE p.quantite_en_stock > 0
            ORDER BY p.nom ASC
        ");
        $req_products->execute();
        $products = $req_products->fetchAll(PDO::FETCH_ASSOC);

    } else {
        // --- CAS 2: PAS DE CRÉANCE EN COURS (AFFICHAGE HISTORIQUE DES CRÉANCES) ---
        
        // Récupère les 10 dernières créances avec leur montant total dû
        $req_creances = $connexion->prepare("
            SELECT c.id, c.date, c.creancier, c.echeance, c.statut, 
                   COALESCE(SUM(lc.quantite * lc.prix), 0) as montant_total
            FROM `creance` c
            LEFT JOIN `ligne_creance` lc ON c.id = lc.creance
            GROUP BY c.id, c.date, c.creancier, c.echeance, c.statut
            ORDER BY c.id DESC LIMIT 10
        ");
        $req_creances->execute();
        $creance_lines = $req_creances->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // En cas d'erreur de connexion ou de requête
    $_SESSION['message'] = ['type' => 'danger', 'text' => "Erreur DB: " . $e->getMessage()];
}
// Ferme la connexion après la récupération des données
$connexion = null;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crédits | Gestion des Créances</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }

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
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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
            border-color: #007bff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .text-danger {
            color: #dc3545;
        }

        /* Style pour Select2 */
        .select2-container--default .select2-selection--single {
            border: 1px solid #ced4da !important;
            height: 40px !important;
            padding: 0.375rem 0.75rem !important;
            border-radius: 0.5rem !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 25px;
        }

        .select2-search--dropdown .select2-search__field {
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            padding: 0.5rem;
        }
    </style>
</head>

<body class="bg-gray-100 text-gray-900 font-sans">
    <div class="flex h-screen">

        <?php include_once('aside.php'); // Assurez-vous que ce chemin est correct ?>

        <div class="flex-1 flex flex-col">

            <header class="flex items-center justify-between bg-white shadow px-6 py-3">
                <h1 class="text-xl font-bold">Crédits</h1>
                <div class="flex items-center space-x-4">
                    <button class="relative">
                        <i class="bi bi-bell text-xl"></i>
                        <span class="absolute -top-1 -right-1 w-2 h-2 bg-blue-500 rounded-full"></span>
                    </button>
                    <div class="flex items-center space-x-2">
                        <img src="https://placehold.co/128x128/E5E7EB/6B7280?text=U" class="w-8 h-8 rounded-full" alt="Avatar">
                        <span class="hidden md:block">User</span>
                    </div>
                </div>
            </header>

            <main class="p-6 overflow-y-auto flex-1">
                <?php include_once('message.php'); // Affiche les messages de session ?>

                <?php if ($current_creance_id) : ?>
                    <h2 class="text-2xl font-semibold mb-2">Créance en cours: <span class="text-blue-700">#<?= $current_creance_id ?> (<?= $creancier_name ?>)</span></h2>
                    <p class="mb-4 text-sm text-gray-600">Échéance prévue: <span class="font-bold text-red-500"><?= $echeance_date ?></span></p>
                    <div class="mb-4 flex space-x-4 flex-wrap gap-2">
                        <button id="openAddLineModalBtn" class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded-lg shadow-md transition-transform transform hover:scale-105">
                            <i class="bi bi-plus-circle-fill"></i> Ajouter un produit au crédit
                        </button>
                        <a href="../traitement/gestion_creances.php?action=finalize_creance" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow-md transition-transform transform hover:scale-105">
                            <i class="bi bi-check-circle-fill"></i> Finaliser la Créance
                        </a>
                        <a href="creance_details.php?id=<?= $current_creance_id ?>" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg shadow-md transition-transform transform hover:scale-105">
                            <i class="bi bi-wallet-fill"></i> Gérer Paiements
                        </a>
                    </div>
                <?php else : ?>
                    <h2 class="text-2xl font-semibold mb-4">Gestion des crédits et historique</h2>
                    <div class="mt-6 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 sm:space-x-4">
                        <div class="relative w-full flex-1">
                            <input type="text" id="searchInput" placeholder="Rechercher une créance..." class="form-control pl-10">
                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                        <button id="openNewCreanceModalBtn" class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded-lg shadow-md transition-transform transform hover:scale-105 whitespace-nowrap w-full sm:w-auto">
                            <i class="bi bi-journal-plus"></i> Nouvelle Créance
                        </button>
                    </div>
                <?php endif; ?>

                <div class="overflow-x-auto mt-4">
                    <table class="min-w-full bg-white shadow rounded-lg">
                        <thead class="bg-blue-600 text-white">
                            <tr>
                                <th class="px-4 py-2 text-center">N°</th>
                                <th class="px-4 py-2 text-left">
                                    <?= $current_creance_id ? 'Article' : 'Créancier / N° Créance' ?>
                                </th>
                                <th class="px-4 py-2 text-center">
                                    <?= $current_creance_id ? 'Quantité' : 'Échéance' ?>
                                </th>
                                <th class="px-4 py-2 text-right">
                                    <?= $current_creance_id ? 'Prix total (Ligne)' : 'Montant Total' ?>
                                </th>
                                <th class="px-4 py-2 text-center">
                                    <?= $current_creance_id ? 'Action' : 'Statut/Actions' ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="creancesTableBody">
                            <?php if (empty($creance_lines)) : ?>
                                <tr>
                                    <td colspan="5" class="py-3 px-6 text-center text-gray-500">
                                        <?= $current_creance_id ? 'Aucun produit n\'a été ajouté à cette créance.' : 'Aucune créance récente n\'a été enregistrée.' ?>
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($creance_lines as $index => $item) : ?>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 text-center"><?= $index + 1 ?></td>

                                        <?php if ($current_creance_id) : // LIGNES DE LA CRÉANCE EN COURS ?>
                                            <td class="px-4 py-2 text-left"><?= htmlspecialchars($item['nom_produit']) ?></td>
                                            <td class="px-4 py-2 text-center"><?= htmlspecialchars($item['quantite']) ?></td>
                                            <td class="px-4 py-2 text-right">
                                                $<?= number_format(htmlspecialchars($item['prix'] * $item['quantite']), 2, ',', ' ') ?>
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <a href="../traitement/gestion_lignes_creance.php?action=delete_line&id=<?= htmlspecialchars($item['id']) ?>" 
                                                   onclick="return confirm('Êtes-vous sûr de vouloir retirer ce produit de la créance?')"
                                                   class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm transition-transform transform hover:scale-105">
                                                    <i class="bi bi-trash-fill"></i> Retirer
                                                </a>
                                            </td>
                                        <?php else : // HISTORIQUE DES CRÉANCES ?>
                                            <td class="px-4 py-2 text-left">
                                                #<?= htmlspecialchars($item['id']) ?> <b>Client: </b><?= htmlspecialchars($item['creancier']) ?>
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <?= htmlspecialchars(date('d/m/Y', strtotime($item['echeance']))) ?>
                                            </td>
                                            <td class="px-4 py-2 text-right">
                                                $<?= number_format(htmlspecialchars($item['montant_total']), 2, ',', ' ') ?>
                                            </td>
                                            <td class="px-4 py-2 flex items-center justify-end space-x-2">
                                                <span class="text-sm font-semibold px-3 py-1 rounded 
                                                    <?php
                                                    if ($item['statut'] == 0) echo 'bg-red-500 text-white';
                                                    else if ($item['statut'] == 1) echo 'bg-green-500 text-white';
                                                    else echo 'bg-gray-400 text-white';
                                                    ?>">
                                                    <?= ($item['statut'] == 0) ? 'Impayé' : 'Soldé' ?>
                                                </span>
                                                <a href="creance_details.php?id=<?= htmlspecialchars($item['id']) ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition-transform transform hover:scale-105">
                                                    <i class="bi bi-eye-fill"></i> Voir
                                                </a>
                                            </td>
                                        <?php endif; ?>

                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>

            <footer class="bg-white shadow px-6 py-3 text-sm flex justify-between">
                <p>2024 &copy; Sainte_Croix</p>
                <p>Crafted with <span class="text-red-500"><i class="bi bi-heart-fill"></i></span> by <a href="#" class="text-red-600">Glad</a></p>
            </footer>
        </div>
    </div>

    <div id="newCreanceModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay">
        <div class="bg-white p-6 rounded-lg shadow-xl modal-content relative">
            <button id="closeNewCreanceModalBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-xl transition-all hover:rotate-90">
                &times;
            </button>
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-4 px-6 mb-4 rounded-t-lg flex items-center justify-center">
                <i class="bi bi-person-lines-fill text-2xl mr-2"></i>
                <h4 class="text-center text-xl font-bold">Démarrer une nouvelle créance</h4>
            </div>
            <form id="newCreanceForm" action="../traitement/gestion_creances.php" method="POST" class="p-3 rounded-b-lg">
                <div class="col-12 p-3">
                    <label for="creancier_select" class="block mb-2">Nom du Créancier/Client <span class="text-danger">*</span></label>
                    <select required name="creancier_name" id="creancier_select" class="form-control" style="width: 100%;">
                        <option value="">Sélectionner ou taper un nouveau client</option>
                        
                        <?php foreach ($existing_creanciers as $creancier) : 
                            $value_to_send = htmlspecialchars($creancier['matricule']);
                            $display_text = htmlspecialchars('[' . $creancier['matricule'] . '] ' . $creancier['nom'] . ' ' . $creancier['prenom']);
                        ?>
                            <option value="<?= $value_to_send ?>">
                                <?= $display_text ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>
                <div class="col-12 p-3">
                    <label for="echeance_date" class="block mb-2">Date d'échéance <span class="text-danger">*</span></label>
                    <input required type="date" name="echeance_date" id="echeance_date" class="form-control">
                </div>
                <div class="col-12 p-3">
                    <input type="hidden" name="action" value="start_creance">
                    <input type="submit" class="btn btn-success w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300" name="Valider" value="Démarrer la créance">
                </div>
            </form>
        </div>
    </div>

    <div id="addEditModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay">
        <div class="bg-white p-6 rounded-lg shadow-xl modal-content relative">
            <button id="closeAddEditModalBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-xl transition-all hover:rotate-90">
                &times;
            </button>
            <div class="w-full">
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-4 px-6 mb-4 rounded-t-lg flex items-center justify-center">
                    <i class="bi bi-plus-circle-fill text-2xl mr-2"></i>
                    <h4 id="addEditModalTitle" class="text-center text-xl font-bold">Ajouter un produit à la créance</h4>
                </div>
                <form id="creanceLineForm" action="../traitement/gestion_lignes_creance.php" method="POST" class="p-3 rounded-b-lg">
                    <div class="row">
                        <div class="col-12 p-3">
                            <label for="id_produit" class="block mb-2">Produit <span class="text-danger">*</span></label>
                            <select required name="id_produit" id="id_produit" class="form-control">
                                <option value="">Sélectionnez un produit</option>
                                <?php foreach ($products as $product) : ?>
                                    <option value="<?= htmlspecialchars($product['id']) ?>" data-prix="<?= htmlspecialchars($product['prix']) ?>" data-stock="<?= htmlspecialchars($product['stock_disponible']) ?>">
                                        <?= htmlspecialchars($product['nom']) ?> ($<?= htmlspecialchars($product['prix']) ?>) (Stock: <?= htmlspecialchars($product['stock_disponible']) ?> pcs)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 p-3">
                            <label for="quantite" class="block mb-2">Quantité <span class="text-danger">*</span></label>
                            <input required type="number" name="quantite" id="quantite" class="form-control" placeholder="Entrez la quantité mise à crédit" min="1">
                            <p id="stockAlert" class="text-sm text-danger mt-1 hidden">Stock insuffisant !</p>
                        </div>
                        <div class="col-12 p-3">
                            <label for="prix_unitaire" class="block mb-2">Prix unitaire (peut être ajusté) <span class="text-danger">*</span></label>
                            <input required type="number" step="0.01" name="prix_unitaire" id="prix_unitaire" class="form-control" placeholder="Prix unitaire">
                        </div>
                        <input type="hidden" name="prix_total_calculé" id="prix_total_calculé" value="0.00"> 
                        <div class="col-12 p-3">
                            <input type="hidden" name="action" value="add_line">
                            <input type="submit" class="btn btn-success w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300" name="Valider" id="submitBtn" value="Ajouter à la créance">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialisation de Select2 pour l'ajout de produit
            $('#id_produit').select2({
                dropdownParent: $('#addEditModal')
            });

            // Initialisation de Select2 pour la sélection/saisie du Créancier
            $('#creancier_select').select2({
                dropdownParent: $('#newCreanceModal'),
                tags: true, // IMPORTANT: Permet d'entrer de nouvelles valeurs non listées
                createTag: function (params) {
                    var term = $.trim(params.term);
                    if (term === '') {
                        return null;
                    }
                    // Quand on crée un nouveau tag, la valeur et le texte sont le terme saisi
                    return {
                        id: term,
                        text: term
                    };
                }
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            // Modals et boutons
            const openAddLineModalBtn = document.getElementById('openAddLineModalBtn');
            const addEditModal = document.getElementById('addEditModal');
            const closeAddEditModalBtn = document.getElementById('closeAddEditModalBtn');

            const openNewCreanceModalBtn = document.getElementById('openNewCreanceModalBtn');
            const newCreanceModal = document.getElementById('newCreanceModal');
            const closeNewCreanceModalBtn = document.getElementById('closeNewCreanceModalBtn');

            // Éléments pour le formulaire d'ajout de produit
            const idProduitSelect = document.getElementById('id_produit');
            const quantiteInput = document.getElementById('quantite');
            const prixUnitaireInput = document.getElementById('prix_unitaire');
            const prixTotalCacheInput = document.getElementById('prix_total_calculé');
            const stockAlert = document.getElementById('stockAlert');
            const submitBtn = document.getElementById('submitBtn');


            function openModal(modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal(modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            // --- Logique d'ouverture/fermeture des modales ---

            // 1. Ouvrir le modal "Ajouter un produit" (si créance en cours)
            if (openAddLineModalBtn) {
                openAddLineModalBtn.addEventListener('click', () => {
                    document.getElementById('creanceLineForm').reset();
                    // Réinitialiser les champs spécifiques et Select2 (utiliser jQuery pour Select2)
                    $('#id_produit').val('').trigger('change'); 
                    prixUnitaireInput.value = '';
                    prixTotalCacheInput.value = '0.00';
                    stockAlert.classList.add('hidden');
                    submitBtn.disabled = false;
                    openModal(addEditModal);
                });
            }

            // 2. Ouvrir le modal "Nouvelle Créance" (si AUCUNE créance en cours)
            if (openNewCreanceModalBtn) {
                openNewCreanceModalBtn.addEventListener('click', () => {
                    document.getElementById('newCreanceForm').reset();
                    // Réinitialiser Select2 pour la créance
                    $('#creancier_select').val('').trigger('change'); 
                    
                    // Pré-remplir la date d'échéance à J+30 par défaut
                    const today = new Date();
                    today.setDate(today.getDate() + 30);
                    const defaultEcheance = today.toISOString().split('T')[0];
                    document.getElementById('echeance_date').value = defaultEcheance;
                    openModal(newCreanceModal);
                });
            }

            // 3. Fermeture des modales
            closeAddEditModalBtn.addEventListener('click', () => closeModal(addEditModal));
            if (closeNewCreanceModalBtn) {
                closeNewCreanceModalBtn.addEventListener('click', () => closeModal(newCreanceModal));
            }

            // --- Logique de calcul et de vérification de stock ---
            function updatePriceAndStock() {
                const selectedOption = idProduitSelect.options[idProduitSelect.selectedIndex];
                const prixUnitaireStock = selectedOption ? parseFloat(selectedOption.getAttribute('data-prix')) : null;
                // Stock disponible est la valeur que nous avons récupérée en DB (stock_disponible)
                const stockDisponible = selectedOption ? parseInt(selectedOption.getAttribute('data-stock')) : 0; 
                const quantite = parseInt(quantiteInput.value);

                // Réinitialisation des alertes
                stockAlert.classList.add('hidden');
                submitBtn.disabled = false;

                if (selectedOption && idProduitSelect.value && (!prixUnitaireInput.value || parseFloat(prixUnitaireInput.value) === 0)) {
                    // Si un produit est choisi et le prix unitaire est vide ou zéro, on prend le prix de base du stock
                    if (prixUnitaireStock !== null) {
                        prixUnitaireInput.value = prixUnitaireStock.toFixed(2);
                    }
                }

                const prixVenteUnitaire = parseFloat(prixUnitaireInput.value);

                if (quantite && quantite > 0 && !isNaN(prixVenteUnitaire)) {
                    // Vérification de stock
                    if (quantite > stockDisponible) {
                        stockAlert.textContent = `Stock insuffisant ! (${stockDisponible} restants)`;
                        stockAlert.classList.remove('hidden');
                        submitBtn.disabled = true; // Désactive le bouton si le stock est insuffisant
                    }

                    // Calcul du prix total
                    const total = prixVenteUnitaire * quantite;
                    prixTotalCacheInput.value = total.toFixed(2);
                } else {
                    prixTotalCacheInput.value = '0.00';
                }
            }

            // Écouteurs d'événements pour le calcul automatique et la vérification
            // Attention : on utilise 'change' pour idProduitSelect car c'est un Select2
            $('#id_produit').on('change', updatePriceAndStock); 
            quantiteInput.addEventListener('input', updatePriceAndStock);
            prixUnitaireInput.addEventListener('input', updatePriceAndStock);
        });
    </script>
</body>

</html>