<?php
session_start();
include('../connexion/connexion.php');

$creance_id = $_SESSION['current_creance_id'] ?? null;

if (!$creance_id) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => "Vous devez d'abord démarrer une créance."];
    header('Location: ../views/creances.php');
    exit();
}

$action = $_POST['action'] ?? null;

try {
    if ($action === 'add_line' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // --- AJOUTER UN PRODUIT À LA CRÉANCE EN COURS ---
        $produit_id = $_POST['id_produit'] ?? null;
        $quantite = intval($_POST['quantite'] ?? 0);
        $prix_unitaire = floatval($_POST['prix_unitaire'] ?? 0);

        if (!$produit_id || $quantite <= 0 || $prix_unitaire <= 0) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => "Tous les champs de la ligne de créance sont obligatoires et doivent être valides."];
            header('Location: ../views/creances.php');
            exit();
        }
        
        // Calcul du prix total de la ligne
        $prix_total_ligne = $quantite * $prix_unitaire;

        // Note: La vérification de stock devrait idéalement être faite ici aussi
        
        // Insertion de la ligne de créance
        // Rappel des colonnes: `id`, `creance`, `produit`, `quantité`, `prix`, `statut`
        $req_insert = $connexion->prepare("
            INSERT INTO `ligne_creance` (`creance`, `produit`, `quantite`, `prix`, `statut`) 
            VALUES (:creance_id, :produit_id, :quantite, :prix_total_ligne, 0)
        ");
        
        $req_insert->execute([
            'creance_id' => $creance_id,
            'produit_id' => $produit_id,
            'quantite' => $quantite,
            'prix_total_ligne' => $prix_total_ligne
        ]);

        $_SESSION['message'] = ['type' => 'success', 'text' => "Produit ajouté à la créance #{$creance_id}. Prix total ligne: $".number_format($prix_total_ligne, 2, ',', ' ')];
        
    } else {
        $_SESSION['message'] = ['type' => 'danger', 'text' => "Action non supportée pour les lignes de créance."];
    }
} catch (PDOException $e) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => "Erreur DB: " . $e->getMessage()];
}

header('Location: ../views/creances.php');
exit();
?>