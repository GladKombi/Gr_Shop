<?php
// Inclusion du fichier de connexion à la base de données
include('../connexion/connexion.php');

$commande = null;
$lignes_commande = [];
$total_final = 0.0; // Initialisation du total

// Vérifie si un ID de commande est passé dans l'URL (nous passons l'ID de la commande, pas de la vente)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $commande_id = $_GET['id'];

    try {
        // 1. Récupère les détails de la COMMANDE (pour le client, la date et le total final)
        $req_commande = $connexion->prepare("SELECT * FROM `commande` WHERE id = :id");
        $req_commande->execute(['id' => $commande_id]);
        $commande = $req_commande->fetch(PDO::FETCH_ASSOC);

        if ($commande) {
            // 2. Récupère TOUTES les LIGNES DE COMMANDE associées (les produits vendus)
            // Jointure avec 'produits' pour obtenir le nom et le prix unitaire
            $req_lignes = $connexion->prepare("SELECT ligne_commande.*, produits.nom, produits.prix AS prix_unitaire FROM `ligne_commande`, `produits` WHERE ligne_commande.produit_id = produits.id AND ligne_commande.commande_id = :id");
            $req_lignes->execute(['id' => $commande_id]);
            $lignes_commande = $req_lignes->fetchAll(PDO::FETCH_ASSOC);
            
            // Si le prix_total de la commande n'est pas stocké, on le calcule ici
            $total_final = $commande['prix_total'] ?? array_sum(array_column($lignes_commande, 'prix_total'));
        }

    } catch (PDOException $e) {
        // Gérer les erreurs de connexion à la base de données
        // En production, stocker dans un log plutôt que d'afficher à l'utilisateur
    }
}
$connexion = null;

// Gérer le cas où la commande n'est pas trouvée
if (!$commande) {
    echo "<h1>Commande (Facture) introuvable.</h1>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture N° <?= htmlspecialchars($commande['id']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .invoice-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        @media print {
            body {
                background-color: #fff;
            }
            .invoice-container {
                box-shadow: none;
                margin: 0;
                border-radius: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        
        <div class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-3xl font-bold text-red-800">Facture</h1>
                <p class="text-lg text-gray-600">N° <?= htmlspecialchars($commande['id']) ?></p>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-semibold">Angel_Tech</h2>
                <p class="text-sm text-gray-500">Adresse de l'entreprise<br>Téléphone: 000-000-000<br>Email: Angel_Tech@gmail.com</p>
            </div>
        </div>

        <div class="border-t border-b border-gray-200 py-6 mb-8">
            <div class="flex justify-between text-sm">
                <div>
                    <p><strong class="text-gray-700">Client:</strong> <?= htmlspecialchars($commande['nom_client']) ?></p>
                </div>
                <div>
                    <p><strong class="text-gray-700">Date de la facture:</strong> <?= date('d/m/Y', strtotime($commande['date'])) ?></p>
                    <p><strong class="text-gray-700">Heure de la vente:</strong> <?= date('H:i', strtotime($commande['date'])) ?></p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto mb-8">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 text-left font-semibold text-gray-600">Description</th>
                        <th class="py-2 px-4 text-right font-semibold text-gray-600">Prix unitaire</th>
                        <th class="py-2 px-4 text-center font-semibold text-gray-600">Quantité</th>
                        <th class="py-2 px-4 text-right font-semibold text-gray-600">Total (Ligne)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lignes_commande)): ?>
                        <tr>
                            <td colspan="4" class="py-3 px-4 text-center text-gray-500">Aucun produit trouvé pour cette commande.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lignes_commande as $ligne): ?>
                            <tr class="border-b">
                                <td class="py-3 px-4 text-left"><?= htmlspecialchars($ligne['nom']) ?></td>
                                <td class="py-3 px-4 text-right">$<?= number_format(htmlspecialchars($ligne['prix_unitaire']), 2, ',', ' ') ?></td>
                                <td class="py-3 px-4 text-center"><?= htmlspecialchars($ligne['quantite']) ?></td>
                                <td class="py-3 px-4 text-right font-semibold">$<?= number_format(htmlspecialchars($ligne['prix_total']), 2, ',', ' ') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mb-8">
            <div class="w-full sm:w-1/2">
                <div class="flex justify-between border-t border-b border-red-500 py-4 font-bold text-xl bg-red-50 text-red-800">
                    <span class="px-4">MONTANT TOTAL DÛ</span>
                    <span class="px-4">$<?= number_format($total_final, 2, ',', ' ') ?></span>
                </div>
            </div>
        </div>

        <div class="text-center mt-12 text-gray-500 text-sm">
            <p>Merci de votre achat !</p>
        </div>

        <div class="flex justify-center space-x-4 no-print mt-8">
            <a href="ventes.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg shadow-md transition-transform transform hover:scale-105">
                <i class="bi bi-arrow-left-circle-fill mr-2"></i> Retour aux Ventes
            </a>
            <button onclick="window.print()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-transform transform hover:scale-105">
                <i class="bi bi-printer-fill mr-2"></i> Imprimer la Facture
            </button>
        </div>
    </div>
</body>
</html>