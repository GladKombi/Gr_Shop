<?php

// Inclusion du fichier de connexion à la base de données
// Assurez-vous que le chemin est correct pour votre structure de dossiers
include('../connexion/connexion.php');

$sales = [];
$products = [];
// Récupère l'ID de la commande en cours stocké en session, s'il existe
if (isset($_SESSION['current_commande_id'])) {
    $current_commande_id = $_SESSION['current_commande_id'];
} else {
    $current_commande_id = null;
}
// $current_commande_id = isset($_SESSION['current_commande_id']) ? $_SESSION['current_commande_id'] : null;
$client_name = '...'; // Initialisation

try {
    if (!empty($current_commande_id)) {
        // --- CAS 1: COMMANDE EN COURS ---
        // Récupère le nom du client
        $req_client = $connexion->prepare("SELECT nom_client FROM `commande` WHERE id = :id");
        $req_client->execute(['id' => $current_commande_id]);
        $client_data = $req_client->fetch(PDO::FETCH_ASSOC);
        if ($client_data) {
            $client_name = htmlspecialchars($client_data['nom_client']);
        }

        // Récupère toutes les LIGNES DE COMMANDES (les produits dans le panier)
        $req_sales = $connexion->prepare("SELECT `ligne_commande`.*, produits.nom FROM `ligne_commande`,`produits` WHERE ligne_commande.produit_id=produits.id AND ligne_commande.statut=0 AND commande_id = :id ORDER BY id DESC");
        $req_sales->execute(['id' => $current_commande_id]);
        $sales = $req_sales->fetchAll(PDO::FETCH_ASSOC);

        // Récupère tous les produits disponibles pour l'ajout au panier,
        // en calculant le stock disponible réel (Stock Total - Total Vendu)
        $req_products = $connexion->prepare(" SELECT p.id, p.nom, p.prix, p.quantite_en_stock AS stock_total, COALESCE(SUM(lc.quantite), 0) AS total_vendu, (p.quantite_en_stock - COALESCE(SUM(lc.quantite), 0)) AS stock_disponible FROM `produits` p LEFT JOIN `ligne_commande` lc ON p.id = lc.produit_id GROUP BY p.id, p.nom, p.prix, p.quantite_en_stock HAVING stock_disponible > 0 ORDER BY p.nom ASC");
        $req_products->execute();
        $products = $req_products->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // --- CAS 2: PAS DE COMMANDE EN COURS (AFFICHAGE HISTORIQUE DES COMMANDES) ---

        // Récupère les 10 dernières commandes (ID, Client, Montant Total, Date)
        $req_sales = $connexion->prepare("SELECT commande.*, prix_total FROM `ligne_commande`, commande WHERE ligne_commande.id=commande.id AND ligne_commande.statut=0 ORDER BY commande.id DESC LIMIT 10");
        $req_sales->execute();
        $sales = $req_sales->fetchAll(PDO::FETCH_ASSOC);
    }

    // // Récupère tous les produits disponibles pour l'ajout au panier (reste inchangé)
    // $req_products = $connexion->prepare("SELECT id, nom, prix, quantite_en_stock FROM `produits` WHERE quantite_en_stock > 0 ORDER BY nom ASC");
    // $req_products->execute();
    // $products = $req_products->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Ventes | Gestion des Commandes</title>
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
            /* Styles pour les champs de formulaire */
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

        /* Style pour mieux intégrer Select2 à votre thème Tailwind */
        .select2-container--default .select2-selection--single {
            border: 1px solid #ced4da !important;
            height: 40px !important;
            /* Ajuste la hauteur */
            padding: 0.375rem 0.75rem !important;
            border-radius: 0.5rem !important;
            /* Correspond à form-control */
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 25px;
            /* Ajuste le texte rendu */
        }

        /* Assurez-vous que le champ de recherche dans le Select2 est bien stylisé */
        .select2-search--dropdown .select2-search__field {
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            padding: 0.5rem;
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
            
            .btn-group-mobile {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn-group-mobile .btn {
                width: 100%;
                text-align: center;
                justify-content: center;
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
            
            .action-buttons-mobile a,
            .action-buttons-mobile button {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
            
            .order-info-mobile {
                text-align: center;
            }
            
            .order-info-mobile h2 {
                font-size: 1.25rem;
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
        
        .action-buttons a,
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
                        <a href="creanciers.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            <i class="bi bi-people-fill mr-2"></i> Créanciers
                        </a>
                    </li>
                    
                    <li>
                        <a href="ventes.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-700 bg-red-700 transition-colors">
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
                <h1 class="text-xl font-bold">Ventes</h1>
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
                <?php include_once('message.php'); // Affiche les messages de session 
                ?>

                <?php if ($current_commande_id): ?>
                    <div class="order-info-mobile">
                        <h2 class="text-2xl font-semibold mb-2">Commande en cours: <span class="text-red-700">#<?= $current_commande_id ?> (<?= $client_name ?>)</span></h2>
                    </div>
                    <div class="mb-4 flex space-x-4 flex-wrap gap-2 btn-group-mobile">
                        <button id="openAddLineModalBtn" class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-lg shadow-md transition-all hover:scale-105 flex items-center justify-center">
                            <i class="bi bi-plus-circle-fill mr-2"></i> Ajouter un produit
                        </button>
                        <a href="../traitement/gestion_commandes.php?action=finalize_order" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow-md transition-all hover:scale-105 flex items-center justify-center">
                            <i class="bi bi-cash mr-2"></i> Finaliser
                        </a>
                    </div>
                <?php else: ?>
                    <h2 class="text-2xl font-semibold mb-4">Gestion des commandes et historique</h2>
                    <div class="mt-6 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 sm:space-x-4 search-bar-mobile">
                        <div class="relative w-full flex-1">
                            <input type="text" id="searchInput" placeholder="Rechercher une vente..." class="form-control pl-10">
                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                        <button id="openNewOrderModalBtn" class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-lg shadow-md transition-all hover:scale-105 whitespace-nowrap w-full sm:w-auto flex items-center justify-center btn-mobile-full">
                            <i class="bi bi-cart-plus-fill mr-2"></i> Nouvelle Commande
                        </button>
                    </div>
                <?php endif; ?>

                <div class="overflow-x-auto mt-4 table-container">
                    <table class="min-w-full bg-white shadow rounded-lg">
                        <thead class="bg-red-600 text-white">
                            <tr>
                                <th class="px-4 py-2 text-center table-cell-mobile">N°</th>
                                <th class="px-4 py-2 text-left table-cell-mobile">
                                    <?= $current_commande_id ? 'Article' : 'Client / N° Commande' ?>
                                </th>
                                <th class="px-4 py-2 text-center table-cell-mobile">
                                    <?= $current_commande_id ? 'Quantité' : 'Date' ?>
                                </th>
                                <th class="px-4 py-2 text-right table-cell-mobile">
                                    <?= $current_commande_id ? 'Prix total (Ligne)' : 'Montant Total' ?>
                                </th>
                                <th class="px-4 py-2 text-center table-cell-mobile">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="salesTableBody">
                            <?php if (empty($sales)): ?>
                                <tr>
                                    <td colspan="5" class="py-3 px-6 text-center text-gray-500">
                                        <?= $current_commande_id ? 'Le panier est vide. Ajoutez des produits.' : 'Aucune commande récente n\'a été enregistrée.' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sales as $index => $sale): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-4 py-2 text-center table-cell-mobile"><?= $index + 1 ?></td>

                                        <?php if ($current_commande_id): ?>
                                            <td class="px-4 py-2 text-left table-cell-mobile font-medium"><?= htmlspecialchars($sale['nom']) ?></td>
                                            <td class="px-4 py-2 text-center table-cell-mobile"><?= htmlspecialchars($sale['quantite']) ?></td>
                                            <td class="px-4 py-2 text-right table-cell-mobile font-semibold">
                                                $<?= number_format(htmlspecialchars($sale['prix_total']), 2, ',', ' ') ?>
                                            </td>
                                            <td class="px-4 py-2">
                                                <!-- Actions pour les lignes de commande en cours -->
                                            </td>
                                        <?php else: ?>
                                            <td class="px-4 py-2 text-left table-cell-mobile">
                                                <div class="font-semibold">#<?= htmlspecialchars($sale['id']) ?></div>
                                                <div class="text-sm text-gray-600">Client: <?= htmlspecialchars($sale['nom_client']) ?></div>
                                            </td>
                                            <td class="px-4 py-2 text-center table-cell-mobile">
                                                <?= htmlspecialchars(date('Y-m-d', strtotime($sale['date']))) ?>
                                            </td>
                                            <td class="px-4 py-2 text-right table-cell-mobile font-semibold">
                                                $<?= number_format(htmlspecialchars($sale['prix_total']), 2, ',', ' ') ?>
                                            </td>
                                            <td class="px-4 py-2">
                                                <div class="action-buttons action-buttons-mobile">
                                                    <a href="facture.php?id=<?= htmlspecialchars($sale['id']) ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition-all hover:scale-105 flex items-center">
                                                        <i class="bi bi-file-earmark-text-fill mr-1"></i> Facture
                                                    </a>
                                                </div>
                                            </td>
                                        <?php endif; ?>

                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>

            <footer class="bg-white shadow px-6 py-3 text-sm flex flex-col md:flex-row justify-between items-center footer-mobile">
                <p>2024 &copy; Sainte_Croix</p>
                <p class="mt-2 md:mt-0">Crafted with <span class="text-red-500"><i class="bi bi-heart-fill"></i></span> by <a href="#" class="text-red-600">Glad</a></p>
            </footer>
        </div>
    </div>

    <div id="newOrderModal" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-overlay">
        <div class="bg-white p-6 rounded-lg shadow-xl modal-content relative">
            <button id="closeNewOrderModalBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-xl transition-all hover:rotate-90">
                &times;
            </button>
            <div class="bg-gradient-to-r from-red-600 to-red-800 text-white py-4 px-6 mb-4 rounded-t-lg flex items-center justify-center">
                <i class="bi bi-person-circle text-2xl mr-2"></i>
                <h4 class="text-center text-xl font-bold">Démarrer une nouvelle commande</h4>
            </div>
            <form id="newOrderForm" action="../traitement/gestion_commandes.php" method="POST" class="p-3 rounded-b-lg">
                <div class="col-12 p-3">
                    <label for="client_name" class="block mb-2">Nom du Client <span class="text-danger">*</span></label>
                    <input required type="text" name="client_name" id="client_name" class="form-control" placeholder="Entrez le nom du client">
                </div>
                <div class="col-12 p-3">
                    <input type="hidden" name="action" value="start_order">
                    <input type="submit" class="btn btn-success w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300 cursor-pointer" name="Valider" value="Démarrer la commande">
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
                <div class="bg-gradient-to-r from-red-600 to-red-800 text-white py-4 px-6 mb-4 rounded-t-lg flex items-center justify-center">
                    <i class="bi bi-plus-circle-fill text-2xl mr-2"></i>
                    <h4 id="addEditModalTitle" class="text-center text-xl font-bold">Ajouter un produit au panier</h4>
                </div>
                <form id="saleForm" action="../traitement/gestion_lignes_commande.php" method="POST" class="p-3 rounded-b-lg">
                    <div class="space-y-4">
                        <div>
                            <label for="id_produit" class="block mb-2">Produit <span class="text-danger">*</span></label>
                            <select required name="id_produit" id="id_produit" class="form-control">
                                <option value="">Sélectionnez un produit</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= htmlspecialchars($product['id']) ?>"
                                        data-prix="<?= htmlspecialchars($product['prix']) ?>"
                                        data-stock="<?= htmlspecialchars($product['stock_disponible']) ?>">
                                        <?= htmlspecialchars($product['nom']) ?> ($<?= htmlspecialchars($product['prix']) ?>) (Stock: <?= htmlspecialchars($product['stock_disponible']) ?> pcs)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="quantite" class="block mb-2">Quantité <span class="text-danger">*</span></label>
                            <input required type="number" name="quantite" id="quantite" class="form-control" placeholder="Entrez la quantité vendue" min="1">
                            <p id="stockAlert" class="text-sm text-danger mt-1 hidden">Stock insuffisant !</p>
                        </div>
                        <div>
                            <label for="prix_total" class="block mb-2">Prix de vente <span class="text-danger">*</span></label>
                            <input required type="number" step="0.01" name="prix_total" id="prix_total" class="form-control" readonly>
                        </div>
                        <div>
                            <input type="submit" class="btn btn-success w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300 cursor-pointer" name="Valider" id="submitBtn" value="Ajouter au panier">
                        </div>
                    </div>
                </form>
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

            // Initialisation de Select2 pour l'ajout de produit
            $('#id_produit').select2({
                dropdownParent: $('#addEditModal')
            });

            // Modals et boutons pour l'ajout de produit (ligne de commande)
            const openAddLineModalBtn = document.getElementById('openAddLineModalBtn');
            const addEditModal = document.getElementById('addEditModal');
            const closeAddEditModalBtn = document.getElementById('closeAddEditModalBtn');
            const saleForm = document.getElementById('saleForm');

            // Nouveaux éléments pour la commande client
            const openNewOrderModalBtn = document.getElementById('openNewOrderModalBtn'); // Le bouton d'ouverture
            const newOrderModal = document.getElementById('newOrderModal'); // Le modal lui-même
            const closeNewOrderModalBtn = document.getElementById('closeNewOrderModalBtn'); // NOUVEAU : Le bouton de fermeture

            // Éléments pour le formulaire d'ajout de produit
            const idProduitSelect = document.getElementById('id_produit');
            const quantiteInput = document.getElementById('quantite');
            const prixTotalInput = document.getElementById('prix_total');
            const stockAlert = document.getElementById('stockAlert');
            const submitBtn = document.getElementById('submitBtn');


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

            // --- Logique d'ouverture/fermeture des modales ---

            // 1. Ouvrir le modal "Ajouter un produit" (si commande en cours)
            if (openAddLineModalBtn) {
                openAddLineModalBtn.addEventListener('click', () => {
                    saleForm.reset();
                    prixTotalInput.value = '';
                    stockAlert.classList.add('hidden');
                    submitBtn.disabled = false;
                    openModal(addEditModal);
                });
            }

            // 2. Ouvrir le modal "Nouvelle Commande" (si AUCUNE commande en cours)
            if (openNewOrderModalBtn) {
                openNewOrderModalBtn.addEventListener('click', () => {
                    openModal(newOrderModal);
                });
            }

            // 3. Fermeture du modal d'ajout de produit
            closeAddEditModalBtn.addEventListener('click', () => closeModal(addEditModal));

            // 4. NOUVEAU: Fermeture du modal de nouvelle commande
            if (closeNewOrderModalBtn) {
                closeNewOrderModalBtn.addEventListener('click', () => closeModal(newOrderModal));
            }

            // Fermer les modales en cliquant à l'extérieur
            [addEditModal, newOrderModal].forEach(modal => {
                if (modal) {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) {
                            closeModal(modal);
                        }
                    });
                }
            });

            // --- Logique de calcul et de vérification de stock ---
            function calculateTotalPrice() {
                const selectedOption = idProduitSelect.options[idProduitSelect.selectedIndex];
                const prixUnitaire = selectedOption ? selectedOption.getAttribute('data-prix') : null;
                const stockDisponible = selectedOption ? parseInt(selectedOption.getAttribute('data-stock')) : 0;
                const quantite = parseInt(quantiteInput.value);

                // Réinitialisation
                prixTotalInput.value = '';
                stockAlert.classList.add('hidden');
                submitBtn.disabled = false;

                if (prixUnitaire && quantite && quantite > 0) {
                    // Vérification de stock
                    if (quantite > stockDisponible) {
                        stockAlert.textContent = `Stock insuffisant ! (${stockDisponible} restants)`;
                        stockAlert.classList.remove('hidden');
                        submitBtn.disabled = true; // Désactive le bouton si le stock est insuffisant
                        return; // Arrête le calcul du prix
                    }

                    // Calcul
                    const total = parseFloat(prixUnitaire) * quantite;
                    prixTotalInput.value = total.toFixed(2);
                }
            }

            // Écouteurs d'événements pour le calcul automatique et la vérification
            // Utiliser l'événement change de Select2
            $('#id_produit').on('change', calculateTotalPrice);
            quantiteInput.addEventListener('input', calculateTotalPrice);

            // Recherche en temps réel dans le tableau
            document.getElementById('searchInput').addEventListener('keyup', function() {
                const filter = this.value.toUpperCase();
                const rows = document.getElementById('salesTableBody').getElementsByTagName('tr');
                
                for (let i = 0; i < rows.length; i++) {
                    const cells = rows[i].getElementsByTagName('td');
                    let showRow = false;
                    
                    // Ignorer la ligne de message vide
                    if (cells.length < 2) continue;
                    
                    // Rechercher dans toutes les cellules de la ligne
                    for (let j = 0; j < cells.length; j++) {
                        const cell = cells[j];
                        if (cell.textContent.toUpperCase().indexOf(filter) > -1) {
                            showRow = true;
                            break;
                        }
                    }
                    
                    rows[i].style.display = showRow ? "" : "none";
                }
            });

            // Touche Échap pour fermer les modales
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (!addEditModal.classList.contains('hidden')) closeModal(addEditModal);
                    if (!newOrderModal.classList.contains('hidden')) closeModal(newOrderModal);
                }
            });
        });
    </script>
</body>

</html>