<?php
// Inclusion du fichier de connexion à la base de données
include('../connexion/connexion.php');

$total_produits = 0;
$total_ventes_nombre = 0;
$chiffre_affaires = 0;
$ventes_par_categorie = [];
$ventes_par_mois = [];
$last_sales = [];
$produits_faible_stock = [];

try {
    // 1. Nombre total de produits
    $req_total_produits = $connexion->prepare("SELECT COUNT(*) FROM `produits`");
    $req_total_produits->execute();
    $total_produits = $req_total_produits->fetchColumn();

    // 2. Nombre total de ventes (nombre de transactions)
    $req_total_ventes = $connexion->prepare("SELECT COUNT(*) FROM `ventes`");
    $req_total_ventes->execute();
    $total_ventes_nombre = $req_total_ventes->fetchColumn();

    // 3. Chiffre d'affaires total
    $req_chiffre_affaires = $connexion->prepare("SELECT SUM(prix_total) FROM `ventes`");
    $req_chiffre_affaires->execute();
    $chiffre_affaires = $req_chiffre_affaires->fetchColumn() ?: 0; // Gérer le cas où le résultat est null

    // 4. Statistiques des ventes par catégorie
    $req_ventes_par_categorie = $connexion->prepare("SELECT p.categorie, SUM(v.quantite) AS total_quantite FROM `ventes` v JOIN `produits` p ON v.produit_id = p.id GROUP BY p.categorie");
    $req_ventes_par_categorie->execute();
    $ventes_par_categorie = $req_ventes_par_categorie->fetchAll(PDO::FETCH_ASSOC);

    // 5. Statistiques des ventes par mois (pour le graphique)
    $req_ventes_par_mois = $connexion->prepare("SELECT DATE_FORMAT(date, '%Y-%m') AS mois, SUM(prix_total) AS total_ventes FROM `ventes` GROUP BY mois ORDER BY mois ASC");
    $req_ventes_par_mois->execute();
    $ventes_par_mois = $req_ventes_par_mois->fetchAll(PDO::FETCH_ASSOC);
    
    // 6. Récupération des 5 dernières ventes
    $req_last_sales = $connexion->prepare("SELECT * FROM `ventes` ORDER BY date DESC LIMIT 5");
    $req_last_sales->execute();
    $last_sales = $req_last_sales->fetchAll(PDO::FETCH_ASSOC);
    
    // 7. Récupère les produits à faible stock (exemple : stock < 10)
    $req_stock = $connexion->prepare("SELECT nom, quantite_en_stock FROM `produits` WHERE quantite_en_stock < 10 ORDER BY quantite_en_stock ASC");
    $req_stock->execute();
    $produits_faible_stock = $req_stock->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // En cas d'erreur de connexion, les valeurs resteront à 0 ou vide
}
$connexion = null;

// Préparation des données pour Chart.js
$labels_mois = json_encode(array_column($ventes_par_mois, 'mois'));
$donnees_ventes_mois = json_encode(array_column($ventes_par_mois, 'total_ventes'));

$labels_categories = json_encode(array_column($ventes_par_categorie, 'categorie'));
$donnees_categories = json_encode(array_column($ventes_par_categorie, 'total_quantite'));
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 text-gray-900 font-sans">
    <div class="flex h-screen">

        <?php include_once('aside.php'); ?>

        <div class="flex-1 flex flex-col">

            <header class="flex items-center justify-between bg-white shadow px-6 py-3">
                <h1 class="text-xl font-bold">Tableau de Bord</h1>
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
                <h2 class="text-2xl font-semibold mb-6">Aperçu des Ventes</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow-md flex items-center">
                        <div class="bg-red-200 text-red-700 p-3 rounded-full mr-4">
                            <i class="bi bi-box-seam-fill text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500">Total Produits</p>
                            <h3 class="text-2xl font-bold"><?= htmlspecialchars($total_produits) ?></h3>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-md flex items-center">
                        <div class="bg-red-200 text-red-700 p-3 rounded-full mr-4">
                            <i class="bi bi-bar-chart-fill text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500">Total Ventes</p>
                            <h3 class="text-2xl font-bold"><?= htmlspecialchars($total_ventes_nombre) ?></h3>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-md flex items-center">
                        <div class="bg-red-200 text-red-700 p-3 rounded-full mr-4">
                            <i class="bi bi-currency-dollar text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500">Chiffre d'Affaires</p>
                            <h3 class="text-2xl font-bold">$<?= number_format($chiffre_affaires, 2, ',', ' ') ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h4 class="text-lg font-semibold mb-4">Actions Rapides</h4>
                    <div class="flex flex-wrap gap-4 justify-between">
                        <a href="produits.php" class="flex-1 min-w-[200px] flex items-center justify-center p-4 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-transform transform hover:scale-105">
                            <i class="bi bi-box-seam-fill text-xl mr-2"></i>
                            Ajouter un Produit
                        </a>
                        <a href="categories.php" class="flex-1 min-w-[200px] flex items-center justify-center p-4 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-transform transform hover:scale-105">
                            <i class="bi bi-tags-fill text-xl mr-2"></i>
                            Ajouter une Catégorie
                        </a>
                        <a href="ventes.php" class="flex-1 min-w-[200px] flex items-center justify-center p-4 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-transform transform hover:scale-105">
                            <i class="bi bi-bar-chart-fill text-xl mr-2"></i>
                            Enregistrer une Vente
                        </a>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h4 class="text-lg font-semibold mb-4">Ventes par Mois</h4>
                        <canvas id="ventesParMoisChart"></canvas>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h4 class="text-lg font-semibold mb-4">Produits vendus par Catégorie</h4>
                        <canvas id="ventesParCategorieChart"></canvas>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md mt-6">
                    <h4 class="text-lg font-semibold mb-4">Dernières Ventes</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                    <th class="py-3 px-6 text-left">Produit</th>
                                    <th class="py-3 px-6 text-left">Quantité</th>
                                    <th class="py-3 px-6 text-left">Prix total</th>
                                    <th class="py-3 px-6 text-left">Date</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm font-light">
                                <?php if (empty($last_sales)): ?>
                                    <tr class="border-b border-gray-200">
                                        <td colspan="4" class="py-3 px-6 text-center">Aucune vente récente.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($last_sales as $sale): ?>
                                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                                            <td class="py-3 px-6 text-left"><?= htmlspecialchars($sale['produit']) ?></td>
                                            <td class="py-3 px-6 text-left"><?= htmlspecialchars($sale['quantite']) ?></td>
                                            <td class="py-3 px-6 text-left">$<?= number_format($sale['prix_total'], 2, ',', ' ') ?></td>
                                            <td class="py-3 px-6 text-left"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($sale['date']))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md mt-6">
                    <h4 class="text-lg font-semibold mb-4">Alerte de Stock Faible</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                    <th class="py-3 px-6 text-left">Produit</th>
                                    <th class="py-3 px-6 text-left">Stock restant</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm font-light">
                                <?php if (empty($produits_faible_stock)): ?>
                                    <tr class="border-b border-gray-200">
                                        <td colspan="2" class="py-3 px-6 text-center">Aucun produit en stock faible.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($produits_faible_stock as $produit): ?>
                                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                                            <td class="py-3 px-6 text-left"><?= htmlspecialchars($produit['nom']) ?></td>
                                            <td class="py-3 px-6 text-left"><span class="bg-yellow-200 text-yellow-800 py-1 px-3 rounded-full text-xs font-bold"><?= htmlspecialchars($produit['quantite_en_stock']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <footer class="bg-white shadow px-6 py-3 text-sm flex justify-between">
                <p>2024 &copy; Sainte_Croix</p>
                <p>Crafted with <span class="text-red-500"><i class="bi bi-heart-fill"></i></span> by <a href="#" class="text-red-600">Glad</a></p>
            </footer>
        </div>
    </div>

    <script>
        // Données passées de PHP à JavaScript
        const labelsMois = <?= $labels_mois ?>;
        const donneesVentesMois = <?= $donnees_ventes_mois ?>;
        const labelsCategories = <?= $labels_categories ?>;
        const donneesCategories = <?= $donnees_categories ?>;

        // Graphique des ventes par mois
        const ventesParMoisCtx = document.getElementById('ventesParMoisChart').getContext('2d');
        const ventesParMoisChart = new Chart(ventesParMoisCtx, {
            type: 'bar',
            data: {
                labels: labelsMois,
                datasets: [{
                    label: 'Ventes totales (en $)',
                    data: donneesVentesMois,
                    backgroundColor: 'rgba(239, 68, 68, 0.6)', // red-500 avec opacité
                    borderColor: 'rgba(239, 68, 68, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Graphique des ventes par catégorie (diagramme circulaire)
        const ventesParCategorieCtx = document.getElementById('ventesParCategorieChart').getContext('2d');
        const ventesParCategorieChart = new Chart(ventesParCategorieCtx, {
            type: 'pie',
            data: {
                labels: labelsCategories,
                datasets: [{
                    label: 'Quantité vendue',
                    data: donneesCategories,
                    backgroundColor: [
                        'rgba(248, 113, 113, 0.8)', // rose-400
                        'rgba(239, 68, 68, 0.8)', // red-500
                        'rgba(220, 38, 38, 0.8)', // red-700
                        'rgba(185, 28, 28, 0.8)', // red-800
                        'rgba(153, 27, 27, 0.8)', // red-900
                    ],
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
            }
        });
    </script>
</body>

</html>