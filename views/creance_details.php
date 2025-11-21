<?php
// Inclusion du fichier de connexion à la base de données
include('../connexion/connexion.php');

// Démarrer la session si ce n'est pas déjà fait pour les messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$creance_id = $_GET['id'] ?? null;
$creance = null;
$creance_lines = [];
$paiements = [];
$total_due = 0;
$total_paid = 0;
$remaining_due = 0;

if (!$creance_id || !is_numeric($creance_id)) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => "Identifiant de créance invalide ou manquant."];
    header('Location: creances.php');
    exit();
}

try {
    // 1. Récupérer les détails de la créance et le numéro du créancier
    $req_creance = $connexion->prepare("SELECT c.id, c.date, c.creancier, c.echeance, c.statut, cr.telephone AS telephone_creancier FROM `creance` c JOIN `creancier` cr ON c.creancier = cr.matricule WHERE c.id = :id");
    $req_creance->execute(['id' => $creance_id]);
    $creance = $req_creance->fetch(PDO::FETCH_ASSOC);

    // Si la créance n'existe pas, on redirige et on s'arrête
    if (!$creance) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => "Créance ou Créancier non trouvé(e)."];
        header('Location: creances.php');
        exit();
    }

    // 2. Récupérer les lignes de créance et calculer le total dû
    $req_lines = $connexion->prepare("SELECT lc.quantite, lc.prix, p.nom as nom_produit FROM `ligne_creance` lc JOIN `produits` p ON lc.produit = p.id WHERE lc.creance = :id");
    $req_lines->execute(['id' => $creance_id]);
    $creance_lines = $req_lines->fetchAll(PDO::FETCH_ASSOC);

    foreach ($creance_lines as $line) {
        $total_due += $line['quantite'] * $line['prix']; 
    }

    // 3. Récupérer l'historique des paiements 
    $req_paiements = $connexion->prepare("SELECT montant, date_paiement FROM `paiements_creance` WHERE creance_id = :id ORDER BY date_paiement DESC");
    $req_paiements->execute(['id' => $creance_id]);
    $paiements = $req_paiements->fetchAll(PDO::FETCH_ASSOC);

    foreach ($paiements as $paiement) {
        $total_paid += $paiement['montant'];
    }

    $remaining_due = $total_due - $total_paid;

} catch (PDOException $e) {
    // Si une erreur de DB survient, on vide les données critiques pour éviter l'erreur
    $creance = null;
    $_SESSION['message'] = ['type' => 'danger', 'text' => "Erreur DB: " . $e->getMessage()];
    // Dans un environnement de production, vous voudriez peut-être rediriger :
    // header('Location: creances.php'); 
    // exit();
}
// Ferme la connexion après la récupération des données
$connexion = null;

// =======================================================
// GÉNÉRATION DU MESSAGE WHATSAPP (MODIFIÉ AVEC VÉRIFICATION)
// =======================================================
$whatsapp_message_text = "Détails non disponibles.";
$whatsapp_url = "#"; // URL par défaut

if ($creance) { // Cette vérification est essentielle
    $whatsapp_message_text = "💰 *Détails de la Créance #{$creance['id']}* 💰\n\n";
    $whatsapp_message_text .= "*Client :* " . htmlspecialchars($creance['creancier']) . "\n";
    $whatsapp_message_text .= "*Date de Création :* " . htmlspecialchars(date('d/m/Y', strtotime($creance['date']))) . "\n";
    $whatsapp_message_text .= "*Échéance :* " . htmlspecialchars(date('d/m/Y', strtotime($creance['echeance']))) . "\n";
    $whatsapp_message_text .= "*Statut :* " . (($creance['statut'] == 0) ? 'IMPAYÉ' : 'SOLDÉ') . "\n";

    $whatsapp_message_text .= "\n*Articles :*\n";
    foreach ($creance_lines as $line) {
        $subtotal = $line['quantite'] * $line['prix'];
        $whatsapp_message_text .= "  - " . htmlspecialchars($line['nom_produit']) . " x " . htmlspecialchars($line['quantite']) . " : $" . number_format($subtotal, 2, ',', ' ') . "\n";
    }

    $whatsapp_message_text .= "\n*Récapitulatif :*\n";
    $whatsapp_message_text .= "*Total Dû :* $" . number_format($total_due, 2, ',', ' ') . "\n";
    $whatsapp_message_text .= "*Total Payé :* $" . number_format($total_paid, 2, ',', ' ') . "\n";
    $whatsapp_message_text .= "➖➖➖➖➖➖➖➖\n";
    $whatsapp_message_text .= "➡️ *Reste à Payer :* $" . number_format($remaining_due, 2, ',', ' ') . "\n";

    // Nettoyage et préparation du numéro de téléphone
    $phone_number = preg_replace('/[^0-9]/', '', $creance['telephone_creancier']);

    // Encodage de l'URL pour le partage WhatsApp AVEC LE NUMÉRO
    $whatsapp_url = "https://wa.me/{$phone_number}?text=" . urlencode($whatsapp_message_text);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Créance #<?= $creance_id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
        }
        
        .form-control { 
            border: 1px solid #d1d5db; 
            padding: 0.5rem 0.75rem; 
            border-radius: 0.375rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            width: 100%;
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
                min-width: 600px;
            }
            
            .grid-cols-1\.lg\:grid-cols-3 {
                grid-template-columns: 1fr;
            }
            
            .lg\:col-span-1, 
            .lg\:col-span-2, 
            .lg\:col-span-3 {
                grid-column: 1;
            }
            
            .payment-form-mobile {
                flex-direction: column;
            }
            
            .payment-form-mobile .flex-1 {
                width: 100%;
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
            
            .creance-info-mobile {
                text-align: center;
            }
            
            .creance-info-mobile p {
                margin-bottom: 0.5rem;
            }
            
            .summary-mobile {
                font-size: 0.875rem;
            }
            
            .table-cell-mobile {
                padding: 0.5rem 0.25rem;
                font-size: 0.875rem;
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
                        <a href="creances.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-red-700 bg-red-700 transition-colors">
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
                <h1 class="text-xl font-bold">Créance #<?= htmlspecialchars($creance['id'] ?? $creance_id) ?></h1>
                <a href="creances.php" class="text-blue-600 hover:text-blue-800 flex items-center">
                    <i class="bi bi-arrow-left-circle-fill mr-2"></i> 
                    <span class="hidden sm:inline">Retour aux Créances</span>
                </a>
            </header>

            <main class="p-6 overflow-y-auto flex-1">
                <?php 
                // Assurez-vous que ce fichier gère l'affichage des messages de session
                // include_once('message.php'); 
                ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <?php if ($creance): ?> 
                        <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md h-full card-hover">
                            <h2 class="text-2xl font-bold mb-4 text-blue-700 flex items-center">
                                <i class="bi bi-info-circle-fill mr-2"></i> 
                                <span class="creance-info-mobile">Résumé</span>
                            </h2>
                            <div class="space-y-3 border-b pb-4 mb-4 creance-info-mobile">
                                <p><strong>Client (Créancier):</strong> <span class="text-lg block mt-1"><?= htmlspecialchars($creance['creancier']) ?></span></p>
                                <p><strong>Téléphone:</strong> <span class="text-lg font-semibold text-gray-700 block mt-1"><?= htmlspecialchars($creance['telephone_creancier']) ?></span></p>
                                <p><strong>Date de Création:</strong> <span class="block mt-1"><?= htmlspecialchars(date('d/m/Y', strtotime($creance['date']))) ?></span></p>
                                <p><strong>Échéance:</strong> <span class="font-semibold text-red-600 block mt-1"><?= htmlspecialchars(date('d/m/Y', strtotime($creance['echeance']))) ?></span></p>
                                <p><strong>Statut:</strong> 
                                    <span class="font-semibold px-3 py-1 rounded text-sm inline-block mt-1
                                        <?php 
                                        if ($creance['statut'] == 0) echo 'bg-red-500 text-white';
                                        else if ($creance['statut'] == 1) echo 'bg-green-500 text-white';
                                        else echo 'bg-gray-400 text-white';
                                        ?>">
                                        <?= ($creance['statut'] == 0) ? 'IMPAYÉ' : 'SOLDÉ' ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div class="space-y-3 font-bold text-lg pt-4 summary-mobile">
                                <p class="flex justify-between items-center">
                                    <span>Total Dû:</span> 
                                    <span class="text-xl">$<?= number_format($total_due, 2, ',', ' ') ?></span>
                                </p>
                                <p class="flex justify-between items-center text-green-600">
                                    <span>Total Payé:</span> 
                                    <span class="text-xl">$<?= number_format($total_paid, 2, ',', ' ') ?></span>
                                </p>
                                <p class="flex justify-between items-center border-t-2 border-dashed pt-2 
                                    <?= ($remaining_due > 0) ? 'text-red-700' : 'text-green-700' ?>">
                                    <span>Reste à Payer:</span> 
                                    <span class="text-xl">$<?= number_format($remaining_due, 2, ',', ' ') ?></span>
                                </p>
                            </div>

                            <div class="mt-6 pt-4 border-t">
                                <a href="<?= $whatsapp_url ?>" target="_blank" 
                                   class="w-full inline-flex items-center justify-center bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 shadow-lg btn-mobile-full">
                                    <i class="bi bi-whatsapp text-xl mr-3"></i> 
                                    Partager sur WhatsApp
                                </a>
                            </div>
                        </div>

                        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md card-hover">
                            <h2 class="text-2xl font-bold mb-4 text-green-700 flex items-center">
                                <i class="bi bi-wallet-fill mr-2"></i> 
                                Paiement et Action
                            </h2>
                            
                            <?php if ($creance['statut'] == 0): // Si la créance n'est pas soldée ?>
                                <form action="../traitement/gestion_creances.php" method="POST" class="space-y-4 p-4 border rounded-lg bg-green-50">
                                    <h3 class="font-semibold text-lg">Enregistrer un nouveau paiement</h3>
                                    <div class="flex flex-col md:flex-row gap-4 payment-form-mobile">
                                        <div class="flex-1">
                                            <label for="montant_paiement" class="block mb-1">Montant Payé <span class="text-danger">*</span></label>
                                            <input required type="number" step="0.01" name="montant" id="montant_paiement" 
                                                class="w-full form-control text-2xl font-bold text-green-700" 
                                                placeholder="Ex: <?= number_format($remaining_due, 2, '.', '') ?>"
                                                max="<?= number_format($remaining_due, 2, '.', '') ?>">
                                        </div>
                                        <div class="flex-1">
                                            <label for="date_paiement" class="block mb-1">Date du Paiement <span class="text-danger">*</span></label>
                                            <input required type="date" name="date_paiement" id="date_paiement" class="w-full form-control" value="<?= date('Y-m-d') ?>">
                                        </div>
                                    </div>
                                    <input type="hidden" name="action" value="record_payment">
                                    <input type="hidden" name="creance_id" value="<?= htmlspecialchars($creance_id) ?>">
                                    <input type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300 cursor-pointer" value="Enregistrer le Paiement">
                                </form>
                                
                                <div class="mt-4">
                                    <a href="../traitement/gestion_creances.php?action=mark_paid&id=<?= htmlspecialchars($creance_id) ?>" 
                                        class="w-full inline-block text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300 btn-mobile-full">
                                        <i class="bi bi-currency-dollar mr-2"></i> 
                                        Marquer comme SOLDÉ
                                    </a>
                                </div>

                            <?php else: ?>
                                <div class="p-4 bg-green-100 border border-green-400 rounded-lg text-green-700 text-center text-xl font-bold">
                                    <i class="bi bi-check-circle-fill mr-2"></i>
                                    Cette créance est SOLDÉE.
                                </div>
                            <?php endif; ?>
                            
                            <h3 class="text-xl font-bold mt-8 mb-3 text-gray-700 border-t pt-4 flex items-center">
                                <i class="bi bi-clock-history mr-2"></i>
                                Historique des Paiements
                            </h3>
                            <div class="overflow-x-auto table-container">
                                <table class="min-w-full bg-white border">
                                    <thead>
                                        <tr class="bg-gray-200">
                                            <th class="px-4 py-2 text-left table-cell-mobile">Date</th>
                                            <th class="px-4 py-2 text-right table-cell-mobile">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($paiements)): ?>
                                            <tr>
                                                <td colspan="2" class="px-4 py-2 text-center text-gray-500 table-cell-mobile">
                                                    Aucun paiement enregistré pour l'instant.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($paiements as $paiement): ?>
                                                <tr class="border-b hover:bg-gray-50">
                                                    <td class="px-4 py-2 table-cell-mobile">
                                                        <?= htmlspecialchars(date('d/m/Y', strtotime($paiement['date_paiement']))) ?>
                                                    </td>
                                                    <td class="px-4 py-2 text-right text-green-600 font-semibold table-cell-mobile">
                                                        $<?= number_format(htmlspecialchars($paiement['montant']), 2, ',', ' ') ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="lg:col-span-3 bg-white p-6 rounded-lg shadow-md mt-6 card-hover">
                            <h2 class="text-2xl font-bold mb-4 text-blue-700 flex items-center">
                                <i class="bi bi-list-check mr-2"></i> 
                                Produits inclus dans la Créance
                            </h2>
                            <div class="overflow-x-auto table-container">
                                <table class="min-w-full bg-white shadow rounded-lg">
                                    <thead class="bg-blue-500 text-white">
                                        <tr>
                                            <th class="px-4 py-2 text-left table-cell-mobile">Produit</th>
                                            <th class="px-4 py-2 text-center table-cell-mobile">Quantité</th>
                                            <th class="px-4 py-2 text-right table-cell-mobile">Prix Unitaire</th>
                                            <th class="px-4 py-2 text-right table-cell-mobile">Prix Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($creance_lines)): ?>
                                            <tr>
                                                <td colspan="4" class="px-4 py-2 text-center text-gray-500 table-cell-mobile">
                                                    Aucun produit trouvé pour cette créance.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($creance_lines as $line): ?>
                                                <tr class="border-b hover:bg-gray-50">
                                                    <td class="px-4 py-2 table-cell-mobile font-medium">
                                                        <?= htmlspecialchars($line['nom_produit']) ?>
                                                    </td>
                                                    <td class="px-4 py-2 text-center table-cell-mobile">
                                                        <?= htmlspecialchars($line['quantite']) ?>
                                                    </td>
                                                    <td class="px-4 py-2 text-right table-cell-mobile">
                                                        $<?= number_format(htmlspecialchars($line['prix']), 2, ',', ' ') ?>
                                                    </td>
                                                    <td class="px-4 py-2 text-right font-semibold table-cell-mobile">
                                                        $<?= number_format(htmlspecialchars($line['quantite'] * $line['prix']), 2, ',', ' ') ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="lg:col-span-3 p-6 bg-red-100 border border-red-400 rounded-lg text-red-700">
                            <p class="font-bold text-xl flex items-center">
                                <i class="bi bi-exclamation-triangle-fill mr-2"></i>
                                Erreur de chargement des détails
                            </p>
                            <p class="mt-2">Les informations sur la créance n°<?= htmlspecialchars($creance_id) ?> n'ont pas pu être chargées. Veuillez vérifier l'identifiant et la connexion à la base de données.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </main>

            <footer class="bg-white shadow px-6 py-3 text-sm flex flex-col md:flex-row justify-between items-center footer-mobile">
                <p>2024 &copy; Sainte_Croix</p>
                <p class="mt-2 md:mt-0">Crafted with <span class="text-red-500"><i class="bi bi-heart-fill"></i></span> by <a href="#" class="text-red-600">Glad</a></p>
            </footer>
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

            // Auto-remplissage du montant maximum restant
            const montantInput = document.getElementById('montant_paiement');
            if (montantInput) {
                montantInput.addEventListener('focus', function() {
                    if (!this.value) {
                        this.value = '<?= number_format($remaining_due, 2, '.', '') ?>';
                    }
                });
            }
        });
    </script>
</body>

</html>