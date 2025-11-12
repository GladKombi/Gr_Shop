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
    // 1. Récupérer les détails de la créance
    $req_creance = $connexion->prepare("SELECT id, date, creancier, echeance, statut FROM `creance` WHERE id = :id");
    $req_creance->execute(['id' => $creance_id]);
    $creance = $req_creance->fetch(PDO::FETCH_ASSOC);

    if (!$creance) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => "Créance non trouvée."];
        header('Location: creances.php');
        exit();
    }

    // 2. Récupérer les lignes de créance et calculer le total dû
    // J'utilise 'quantite' sans accent pour coller au code HTML fourni, MAIS VÉRIFIEZ VOTRE DB
    $req_lines = $connexion->prepare("SELECT lc.quantite, lc.prix, p.nom as nom_produit FROM `ligne_creance` lc JOIN `produits` p ON lc.produit = p.id WHERE lc.creance = :id");
    $req_lines->execute(['id' => $creance_id]);
    $creance_lines = $req_lines->fetchAll(PDO::FETCH_ASSOC);

    foreach ($creance_lines as $line) {
        // Assurez-vous que le nom de colonne est correct ici (quantite vs quantité)
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
    $_SESSION['message'] = ['type' => 'danger', 'text' => "Erreur DB: " . $e->getMessage()];
    // Dans un environnement de production, vous ne devriez pas afficher $e->getMessage() directement
    // header('Location: creances.php'); 
    // exit();
}
// Ferme la connexion après la récupération des données
$connexion = null;

// =======================================================
// GÉNÉRATION DU MESSAGE WHATSAPP (MODIFICATION)
// =======================================================
$whatsapp_message_text = "💰 *Détails de la Créance #{$creance['id']}* 💰\n\n";
$whatsapp_message_text .= "*Client :* " . htmlspecialchars($creance['creancier']) . "\n";
$whatsapp_message_text .= "*Date de Création :* " . htmlspecialchars(date('d/m/Y', strtotime($creance['date']))) . "\n";
$whatsapp_message_text .= "*Échéance :* " . htmlspecialchars(date('d/m/Y', strtotime($creance['echeance']))) . "\n";
$whatsapp_message_text .= "*Statut :* " . (($creance['statut'] == 0) ? 'IMPAYÉ' : 'SOLDÉ') . "\n";

$whatsapp_message_text .= "\n*Articles :*\n";
foreach ($creance_lines as $line) {
    $subtotal = $line['quantite'] * $line['prix'];
    $whatsapp_message_text .= "  - " . htmlspecialchars($line['nom_produit']) . " x " . htmlspecialchars($line['quantite']) . " : $" . number_format($subtotal, 2, ',', ' ') . "\n";
}

$whatsapp_message_text .= "\n*Récapitulatif :*\n";
$whatsapp_message_text .= "*Total Dû :* $" . number_format($total_due, 2, ',', ' ') . "\n";
$whatsapp_message_text .= "*Total Payé :* $" . number_format($total_paid, 2, ',', ' ') . "\n";
$whatsapp_message_text .= "➖➖➖➖➖➖➖➖\n";
$whatsapp_message_text .= "➡️ *Reste à Payer :* $" . number_format($remaining_due, 2, ',', ' ') . "\n";

// Encodage de l'URL pour le partage WhatsApp
$whatsapp_url = "https://wa.me/?text=" . urlencode($whatsapp_message_text);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Créance #<?= htmlspecialchars($creance_id) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Style simple pour form-control si vous n'avez pas de CSS global */
        .form-control {
            border: 1px solid #d1d5db;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body class="bg-gray-100 text-gray-900 font-sans">
    <div class="flex h-screen">
        <?php include_once('aside.php'); ?>

        <div class="flex-1 flex flex-col">
            <header class="flex items-center justify-between bg-white shadow px-6 py-3">
                <h1 class="text-xl font-bold">Créance #<?= htmlspecialchars($creance['id']) ?></h1>
                <a href="creances.php" class="text-blue-600 hover:text-blue-800"><i class="bi bi-arrow-left-circle-fill"></i> Retour aux Créances</a>
            </header>

            <main class="p-6 overflow-y-auto flex-1">
                <?php
                // Assurez-vous que ce fichier gère l'affichage des messages de session
                // include_once('message.php'); 
                ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md h-full">
                        <h2 class="text-2xl font-bold mb-4 text-blue-700"><i class="bi bi-info-circle-fill"></i> Résumé</h2>
                        <div class="space-y-3 border-b pb-4 mb-4">
                            <p><strong>Client (Créancier):</strong> <span class="text-lg"><?= htmlspecialchars($creance['creancier']) ?></span></p>
                            <p><strong>Date de Création:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($creance['date']))) ?></p>
                            <p><strong>Échéance:</strong> <span class="font-semibold text-red-600"><?= htmlspecialchars(date('d/m/Y', strtotime($creance['echeance']))) ?></span></p>
                            <p><strong>Statut:</strong>
                                <span class="font-semibold px-3 py-1 rounded text-sm 
                                    <?php
                                    if ($creance['statut'] == 0) echo 'bg-red-500 text-white';
                                    else if ($creance['statut'] == 1) echo 'bg-green-500 text-white';
                                    else echo 'bg-gray-400 text-white';
                                    ?>">
                                    <?= ($creance['statut'] == 0) ? 'IMPAYÉ' : 'SOLDÉ' ?>
                                </span>
                            </p>
                        </div>

                        <div class="space-y-3 font-bold text-lg pt-4">
                            <p class="flex justify-between">Total Dû: <span>$<?= number_format($total_due, 2, ',', ' ') ?></span></p>
                            <p class="flex justify-between text-green-600">Total Payé: <span>$<?= number_format($total_paid, 2, ',', ' ') ?></span></p>
                            <p class="flex justify-between border-t-2 border-dashed pt-2 
                                <?= ($remaining_due > 0) ? 'text-red-700' : 'text-green-700' ?>">
                                Reste à Payer: <span>$<?= number_format($remaining_due, 2, ',', ' ') ?></span>
                            </p>
                        </div>

                        <div class="mt-6 pt-4 border-t">
                            <a href="<?= $whatsapp_url ?>" target="_blank"
                                class="w-full inline-flex items-center justify-center bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-300 shadow-lg">
                                <i class="bi bi-whatsapp text-xl mr-3"></i>
                                Partager les Détails (WhatsApp)
                            </a>
                        </div>
                    </div>

                    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-2xl font-bold mb-4 text-green-700"><i class="bi bi-wallet-fill"></i> Paiement et Action</h2>

                        <?php if ($creance['statut'] == 0): // Si la créance n'est pas soldée 
                        ?>
                            <form action="../traitement/gestion_creances.php" method="POST" class="space-y-4 p-4 border rounded-lg bg-green-50">
                                <h3 class="font-semibold text-lg">Enregistrer un nouveau paiement</h3>
                                <div class="flex flex-col md:flex-row gap-4">
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
                                <input type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300" value="Enregistrer le Paiement">
                            </form>

                            <div class="mt-4">
                                <a href="../traitement/gestion_creances.php?action=mark_paid&id=<?= htmlspecialchars($creance_id) ?>"
                                    class="w-full inline-block text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300">
                                    <i class="bi bi-currency-dollar"></i> Marquer comme SOLDÉ (même s'il reste un petit montant)
                                </a>
                            </div>

                        <?php else: ?>
                            <div class="p-4 bg-green-100 border border-green-400 rounded-lg text-green-700 text-center text-xl font-bold">
                                Cette créance est SOLDÉE.
                            </div>
                        <?php endif; ?>

                        <h3 class="text-xl font-bold mt-8 mb-3 text-gray-700 border-t pt-4">Historique des Paiements</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border">
                                <thead>
                                    <tr class="bg-gray-200">
                                        <th class="px-4 py-2 text-left">Date</th>
                                        <th class="px-4 py-2 text-right">Montant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($paiements)): ?>
                                        <tr>
                                            <td colspan="2" class="px-4 py-2 text-center text-gray-500">Aucun paiement enregistré pour l'instant.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($paiements as $paiement): ?>
                                            <tr class="border-b">
                                                <td class="px-4 py-2"><?= htmlspecialchars(date('d/m/Y', strtotime($paiement['date_paiement']))) ?></td>
                                                <td class="px-4 py-2 text-right text-green-600 font-semibold">$<?= number_format(htmlspecialchars($paiement['montant']), 2, ',', ' ') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md mt-6">
                    <h2 class="text-2xl font-bold mb-4 text-blue-700"><i class="bi bi-list-check"></i> Produits inclus dans la Créance</h2>
                    <table class="min-w-full bg-white shadow rounded-lg">
                        <thead class="bg-blue-500 text-white">
                            <tr>
                                <th class="px-4 py-2 text-left">Produit</th>
                                <th class="px-4 py-2 text-center">Quantité</th>
                                <th class="px-4 py-2 text-right">Prix Unitaire</th>
                                <th class="px-4 py-2 text-right">Prix Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($creance_lines)): ?>
                                <tr>
                                    <td colspan="4" class="px-4 py-2 text-center text-gray-500">Aucun produit trouvé pour cette créance.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($creance_lines as $line): ?>
                                    <tr class="border-b">
                                        <td class="px-4 py-2"><?= htmlspecialchars($line['nom_produit']) ?></td>
                                        <td class="px-4 py-2 text-center"><?= htmlspecialchars($line['quantite']) ?></td>
                                        <td class="px-4 py-2 text-right">$<?= number_format(htmlspecialchars($line['prix']), 2, ',', ' ') ?></td>
                                        <td class="px-4 py-2 text-right font-semibold">$<?= number_format(htmlspecialchars($line['quantite'] * $line['prix']), 2, ',', ' ') ?></td>
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
</body>

</html>