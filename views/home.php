<?php
include('../connexion/connexion.php');

// Initialisation des variables
$stats = [
    'total_produits' => 0,
    'total_commandes' => 0,
    'chiffre_affaires' => 0,
    'creances_actives' => 0,
    'montant_creances' => 0,
    'ventes_par_categorie' => [],
    'ventes_par_mois' => [],
    'commandes_recentes' => [],
    'produits_faible_stock' => [],
    'creances_impayees' => [],
    'panier_moyen' => 0,
    'produits_populaires' => []
];

try {
    // 1. Nombre total de produits
    $req = $connexion->prepare("SELECT COUNT(*) FROM produits WHERE statut = 0");
    $req->execute();
    $stats['total_produits'] = $req->fetchColumn();

    // 2. Nombre total de commandes
    $req = $connexion->prepare("SELECT COUNT(*) FROM commande WHERE etat = 'Payée'");
    $req->execute();
    $stats['total_commandes'] = $req->fetchColumn();

    // 3. Chiffre d'affaires total
    $req = $connexion->prepare("
        SELECT COALESCE(SUM(lc.prix_total), 0) 
        FROM ligne_commande lc 
        JOIN commande c ON lc.commande_id = c.id 
        WHERE c.etat = 'Payée'
    ");
    $req->execute();
    $stats['chiffre_affaires'] = $req->fetchColumn();

    // 4. Créances actives
    $req = $connexion->prepare("SELECT COUNT(*) FROM creance WHERE statut = 0");
    $req->execute();
    $stats['creances_actives'] = $req->fetchColumn();

    // 5. Montant total des créances
    $req = $connexion->prepare("
        SELECT COALESCE(SUM(lc.quantite * lc.prix), 0) 
        FROM ligne_creance lc 
        JOIN creance c ON lc.creance = c.id 
        WHERE c.statut = 0
    ");
    $req->execute();
    $stats['montant_creances'] = $req->fetchColumn();

    // 6. Ventes par catégorie
    $req = $connexion->prepare("
        SELECT c.description as categorie, 
               SUM(lc.quantite) as total_quantite,
               SUM(lc.prix_total) as total_ventes
        FROM ligne_commande lc
        JOIN produits p ON lc.produit_id = p.id
        JOIN categories c ON p.categorie = c.id
        JOIN commande cmd ON lc.commande_id = cmd.id
        WHERE cmd.etat = 'Payée'
        GROUP BY c.description
    ");
    $req->execute();
    $stats['ventes_par_categorie'] = $req->fetchAll(PDO::FETCH_ASSOC);

    // 7. Ventes par mois
    $req = $connexion->prepare("
        SELECT DATE_FORMAT(c.date, '%Y-%m') as mois,
               SUM(lc.prix_total) as total_ventes,
               COUNT(DISTINCT c.id) as nb_commandes
        FROM commande c
        JOIN ligne_commande lc ON c.id = lc.commande_id
        WHERE c.etat = 'Payée'
        GROUP BY mois
        ORDER BY mois DESC
        LIMIT 12
    ");
    $req->execute();
    $stats['ventes_par_mois'] = $req->fetchAll(PDO::FETCH_ASSOC);

    // 8. Commandes récentes
    $req = $connexion->prepare("
        SELECT c.*, 
               COUNT(lc.id) as nb_produits,
               SUM(lc.prix_total) as montant_total
        FROM commande c
        LEFT JOIN ligne_commande lc ON c.id = lc.commande_id
        WHERE c.etat = 'Payée'
        GROUP BY c.id
        ORDER BY c.date DESC
        LIMIT 6
    ");
    $req->execute();
    $stats['commandes_recentes'] = $req->fetchAll(PDO::FETCH_ASSOC);

    // 9. Produits à faible stock
    $req = $connexion->prepare("
        SELECT nom, quantite_en_stock, prix
        FROM produits
        WHERE quantite_en_stock < 10 AND statut = 0
        ORDER BY quantite_en_stock ASC
        LIMIT 8
    ");
    $req->execute();
    $stats['produits_faible_stock'] = $req->fetchAll(PDO::FETCH_ASSOC);

    // 10. Créances impayées
    $req = $connexion->prepare("
        SELECT cr.id, cr.creancier, cr.date, cr.echeance,
               SUM(lc.quantite * lc.prix) as montant_total,
               cre.nom, cre.prenom
        FROM creance cr
        JOIN ligne_creance lc ON cr.id = lc.creance
        JOIN creancier cre ON cr.creancier = cre.matricule
        WHERE cr.statut = 0
        GROUP BY cr.id
        ORDER BY cr.echeance ASC
        LIMIT 5
    ");
    $req->execute();
    $stats['creances_impayees'] = $req->fetchAll(PDO::FETCH_ASSOC);

    // 11. Panier moyen
    $req = $connexion->prepare("
        SELECT AVG(montant) as panier_moyen FROM (
            SELECT SUM(lc.prix_total) as montant
            FROM ligne_commande lc
            JOIN commande c ON lc.commande_id = c.id
            WHERE c.etat = 'Payée'
            GROUP BY c.id
        ) as commandes
    ");
    $req->execute();
    $stats['panier_moyen'] = $req->fetchColumn() ?: 0;

    // 12. Produits populaires
    $req = $connexion->prepare("
        SELECT p.nom, 
               SUM(lc.quantite) as total_vendu,
               SUM(lc.prix_total) as chiffre_affaires
        FROM produits p
        JOIN ligne_commande lc ON p.id = lc.produit_id
        JOIN commande c ON lc.commande_id = c.id
        WHERE c.etat = 'Payée'
        GROUP BY p.id
        ORDER BY total_vendu DESC
        LIMIT 5
    ");
    $req->execute();
    $stats['produits_populaires'] = $req->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erreur dashboard: " . $e->getMessage());
}

$connexion = null;

// Préparation des données pour les graphiques
$labels_mois = json_encode(array_column($stats['ventes_par_mois'], 'mois'));
$donnees_ventes_mois = json_encode(array_column($stats['ventes_par_mois'], 'total_ventes'));

$labels_categories = json_encode(array_column($stats['ventes_par_categorie'], 'categorie'));
$donnees_categories = json_encode(array_column($stats['ventes_par_categorie'], 'total_quantite'));

// Extraction pour faciliter l'utilisation dans le HTML
extract($stats);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Gestion Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        /* Masquer les effets hover sur mobile */
        @media (max-width: 768px) {
            .card-hover:hover {
                transform: none;
            }
        }
        /* Animation pour le menu mobile */
        .sidebar-mobile {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }
        .sidebar-mobile.open {
            transform: translateX(0);
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 30;
        }
        .overlay.active {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
    <!-- Overlay pour mobile -->
    <div class="overlay" id="overlay"></div>

    <div class="flex min-h-screen">
        <!-- Sidebar pour desktop -->
        <aside class="w-64 bg-red-900 text-white flex-col transition-transform duration-300 fixed h-full z-40 hidden lg:flex">
            <div class="p-6 text-center text-2xl font-bold border-b border-red-700">
                GR_Shop
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
                        <a href="produits.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-700 bg-red-700 transition-colors">
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
                        <a href="ventes.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            <i class="bi bi-bar-chart-fill mr-2"></i> Ventes
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Sidebar pour mobile -->
        <aside class="w-64 bg-red-900 text-white flex-col fixed h-full z-50 sidebar-mobile lg:hidden" id="mobileSidebar">
            <div class="p-6 text-center text-2xl font-bold border-b border-red-700 flex justify-between items-center">
                <span>GR_Shop</span>
                <button class="text-2xl" id="closeMobileSidebar">
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
                        <a href="produits.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-700 bg-red-700 transition-colors">
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
                        <a href="ventes.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            <i class="bi bi-bar-chart-fill mr-2"></i> Ventes
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Contenu principal -->
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <!-- Header responsive -->
            <header class="bg-white shadow-sm border-b border-gray-200 px-4 sm:px-6 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <!-- Bouton menu mobile -->
                        <button class="lg:hidden text-gray-600 hover:text-gray-900" id="openMobileSidebar">
                            <i class="bi bi-list text-xl"></i>
                        </button>
                        <div>
                            <h1 class="text-lg sm:text-xl font-bold text-gray-900">Tableau de Bord</h1>
                            <p class="text-xs sm:text-sm text-gray-600 hidden sm:block">Aperçu général de votre activité</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="relative">
                            <i class="bi bi-bell text-lg text-gray-600 hover:text-gray-900 cursor-pointer"></i>
                            <span class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </div>
                        <div class="flex items-center space-x-2 bg-gray-100 rounded-lg px-3 py-1">
                            <img src="https://placehold.co/128x128/E5E7EB/6B7280?text=U" class="w-6 h-6 sm:w-8 sm:h-8 rounded-full" alt="Avatar">
                            <span class="text-sm font-medium hidden sm:block">Administrateur</span>
                            <i class="bi bi-chevron-down text-gray-500 text-xs"></i>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                <!-- Cartes de statistiques - Responsive -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                    <!-- Carte Produits -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 card-hover">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium text-gray-600 truncate">Total Produits</p>
                                <h3 class="text-xl font-bold text-gray-900 mt-1"><?= $total_produits ?></h3>
                                <p class="text-xs text-green-600 mt-1">
                                    <i class="bi bi-arrow-up-short"></i>
                                    Actifs
                                </p>
                            </div>
                            <div class="bg-blue-100 p-2 rounded-lg ml-3">
                                <i class="bi bi-box-seam text-lg text-blue-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Carte Commandes -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 card-hover">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium text-gray-600 truncate">Commandes</p>
                                <h3 class="text-xl font-bold text-gray-900 mt-1"><?= $total_commandes ?></h3>
                                <p class="text-xs text-green-600 mt-1">
                                    <i class="bi bi-check-circle"></i>
                                    Payées
                                </p>
                            </div>
                            <div class="bg-green-100 p-2 rounded-lg ml-3">
                                <i class="bi bi-receipt text-lg text-green-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Carte Chiffre d'Affaires -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 card-hover">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium text-gray-600 truncate">Chiffre d'Affaires</p>
                                <h3 class="text-xl font-bold text-gray-900 mt-1">$<?= number_format($chiffre_affaires, 0, ',', ' ') ?></h3>
                                <p class="text-xs text-gray-600 mt-1">
                                    Moyen: $<?= number_format($panier_moyen, 0, ',', ' ') ?>
                                </p>
                            </div>
                            <div class="bg-purple-100 p-2 rounded-lg ml-3">
                                <i class="bi bi-currency-dollar text-lg text-purple-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Carte Créances -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 card-hover">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium text-gray-600 truncate">Créances Actives</p>
                                <h3 class="text-xl font-bold text-gray-900 mt-1"><?= $creances_actives ?></h3>
                                <p class="text-xs text-orange-600 mt-1">
                                    $<?= number_format($montant_creances, 0, ',', ' ') ?> en attente
                                </p>
                            </div>
                            <div class="bg-orange-100 p-2 rounded-lg ml-3">
                                <i class="bi bi-clock-history text-lg text-orange-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphiques et données principales - Responsive -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
                    <!-- Graphique des ventes par mois -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                            <h4 class="text-base font-semibold text-gray-900">Évolution des Ventes</h4>
                            <select class="text-xs border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option>12 derniers mois</option>
                                <option>6 derniers mois</option>
                                <option>30 derniers jours</option>
                            </select>
                        </div>
                        <div class="relative" style="height: 250px;">
                            <canvas id="ventesParMoisChart"></canvas>
                        </div>
                    </div>

                    <!-- Graphique des ventes par catégorie -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                            <h4 class="text-base font-semibold text-gray-900">Ventes par Catégorie</h4>
                            <i class="bi bi-pie-chart text-gray-500 hidden sm:block"></i>
                        </div>
                        <div class="relative" style="height: 250px;">
                            <canvas id="ventesParCategorieChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Grille inférieure - Responsive -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                    <!-- Commandes récentes -->
                    <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                            <h4 class="text-base font-semibold text-gray-900">Commandes Récentes</h4>
                            <a href="commandes.php" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                Voir tout →
                            </a>
                        </div>
                        <div class="space-y-3">
                            <?php if (empty($commandes_recentes)): ?>
                                <div class="text-center py-6 text-gray-500">
                                    <i class="bi bi-receipt text-3xl mb-2 opacity-50"></i>
                                    <p class="text-sm">Aucune commande récente</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($commandes_recentes as $commande): ?>
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors gap-2">
                                        <div class="flex items-center space-x-3">
                                            <div class="bg-green-100 p-2 rounded-lg">
                                                <i class="bi bi-check-lg text-green-600 text-sm"></i>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="font-medium text-gray-900 text-sm truncate"><?= htmlspecialchars($commande['nom_client']) ?></p>
                                                <p class="text-xs text-gray-500"><?= $commande['nb_produits'] ?> produit(s)</p>
                                            </div>
                                        </div>
                                        <div class="text-right sm:text-left">
                                            <p class="font-semibold text-gray-900 text-sm">$<?= number_format($commande['montant_total'], 0, ',', ' ') ?></p>
                                            <p class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($commande['date'])) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Sidebar droite - Responsive -->
                    <div class="space-y-4">
                        <!-- Produits populaires -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                            <h4 class="text-base font-semibold text-gray-900 mb-3">Produits Populaires</h4>
                            <div class="space-y-3">
                                <?php if (empty($produits_populaires)): ?>
                                    <p class="text-gray-500 text-xs text-center">Aucune donnée disponible</p>
                                <?php else: ?>
                                    <?php foreach ($produits_populaires as $produit): ?>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-2 min-w-0 flex-1">
                                                <div class="w-6 h-6 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <i class="bi bi-box text-blue-600 text-xs"></i>
                                                </div>
                                                <span class="font-medium text-xs truncate"><?= htmlspecialchars($produit['nom']) ?></span>
                                            </div>
                                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded flex-shrink-0 ml-2">
                                                <?= $produit['total_vendu'] ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Alertes stock -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-base font-semibold text-gray-900">Alerte Stock</h4>
                                <span class="bg-red-100 text-red-800 text-xs font-medium px-2 py-1 rounded">
                                    <?= count($produits_faible_stock) ?>
                                </span>
                            </div>
                            <div class="space-y-2">
                                <?php if (empty($produits_faible_stock)): ?>
                                    <p class="text-green-600 text-xs font-medium text-center">
                                        <i class="bi bi-check-circle"></i>
                                        Stock optimal
                                    </p>
                                <?php else: ?>
                                    <?php foreach ($produits_faible_stock as $produit): ?>
                                        <div class="flex items-center justify-between p-2 bg-red-50 rounded-lg">
                                            <div class="min-w-0 flex-1">
                                                <p class="font-medium text-xs text-gray-900 truncate"><?= htmlspecialchars($produit['nom']) ?></p>
                                                <p class="text-xs text-gray-600">Stock restant</p>
                                            </div>
                                            <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded flex-shrink-0 ml-2">
                                                <?= $produit['quantite_en_stock'] ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Créances impayées -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-base font-semibold text-gray-900">Créances Impayées</h4>
                                <a href="creances.php" class="text-xs text-orange-600 hover:text-orange-800">
                                    Gérer
                                </a>
                            </div>
                            <div class="space-y-2">
                                <?php if (empty($creances_impayees)): ?>
                                    <p class="text-green-600 text-xs font-medium text-center">
                                        <i class="bi bi-check-circle"></i>
                                        Aucune créance impayée
                                    </p>
                                <?php else: ?>
                                    <?php foreach ($creances_impayees as $creance): ?>
                                        <div class="flex items-center justify-between p-2 bg-orange-50 rounded-lg">
                                            <div class="min-w-0 flex-1">
                                                <p class="font-medium text-xs text-gray-900 truncate"><?= htmlspecialchars($creance['nom'] . ' ' . $creance['prenom']) ?></p>
                                                <p class="text-xs text-gray-600">Échéance: <?= date('d/m/Y', strtotime($creance['echeance'])) ?></p>
                                            </div>
                                            <span class="bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded flex-shrink-0 ml-2">
                                                $<?= number_format($creance['montant_total'], 0, ',', ' ') ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Gestion du menu mobile
        const mobileSidebar = document.getElementById('mobileSidebar');
        const overlay = document.getElementById('overlay');
        const openMobileSidebar = document.getElementById('openMobileSidebar');
        const closeMobileSidebar = document.getElementById('closeMobileSidebar');

        openMobileSidebar.addEventListener('click', () => {
            mobileSidebar.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        closeMobileSidebar.addEventListener('click', () => {
            mobileSidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        });

        overlay.addEventListener('click', () => {
            mobileSidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        });

        // Graphique des ventes par mois (Line Chart)
        const ventesParMoisCtx = document.getElementById('ventesParMoisChart').getContext('2d');
        const ventesParMoisChart = new Chart(ventesParMoisCtx, {
            type: 'line',
            data: {
                labels: <?= $labels_mois ?>,
                datasets: [{
                    label: 'Chiffre d\'affaires',
                    data: <?= $donnees_ventes_mois ?>,
                    borderColor: 'rgb(79, 70, 229)',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: window.innerWidth < 640 ? 10 : 12
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: window.innerWidth < 640 ? 10 : 12
                            }
                        }
                    }
                }
            }
        });

        // Graphique des ventes par catégorie (Doughnut Chart)
        const ventesParCategorieCtx = document.getElementById('ventesParCategorieChart').getContext('2d');
        const ventesParCategorieChart = new Chart(ventesParCategorieCtx, {
            type: 'doughnut',
            data: {
                labels: <?= $labels_categories ?>,
                datasets: [{
                    data: <?= $donnees_categories ?>,
                    backgroundColor: [
                        'rgb(79, 70, 229)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                        'rgb(139, 92, 246)'
                    ],
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: window.innerWidth < 640 ? 10 : 12
                            }
                        }
                    }
                }
            }
        });

        // Redimensionner les graphiques quand la fenêtre change de taille
        window.addEventListener('resize', function() {
            ventesParMoisChart.resize();
            ventesParCategorieChart.resize();
        });
    </script>
</body>
</html>