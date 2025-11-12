<?php
session_start();
include('../connexion/connexion.php');

if (!isset($_POST['action']) && !isset($_GET['action'])) {
    header('Location: ../views/creances.php');
    exit();
}

$action = $_POST['action'] ?? $_GET['action'];

try {
    if ($action === 'start_creance' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // ... (LOGIQUE EXISTANTE POUR DÉMARRER LA CRÉANCE) ...
        $creancier = trim($_POST['creancier_name'] ?? '');
        $echeance = $_POST['echeance_date'] ?? '';

        if (empty($creancier) || empty($echeance)) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => "Le nom du créancier et la date d'échéance sont obligatoires."];
            header('Location: ../views/creances.php');
            exit();
        }

        if (isset($_SESSION['current_creance_id'])) {
            $_SESSION['message'] = ['type' => 'warning', 'text' => "Une créance (#{$_SESSION['current_creance_id']}) est déjà en cours. Veuillez la finaliser d'abord."];
            header('Location: ../views/creances.php');
            exit();
        }

        $req_insert = $connexion->prepare("INSERT INTO `creance` (`date`, `creancier`, `echeance`, `statut`) VALUES (NOW(), :creancier, :echeance, 0)");
        $req_insert->execute(['creancier' => $creancier, 'echeance' => $echeance]);
        $new_creance_id = $connexion->lastInsertId();

        $_SESSION['current_creance_id'] = $new_creance_id;
        $_SESSION['message'] = ['type' => 'success', 'text' => "Nouvelle créance #{$new_creance_id} créée pour {$creancier}. Ajoutez les produits."];
        header('Location: ../views/creances.php');
        exit();

    } elseif ($action === 'finalize_creance' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        // ... (LOGIQUE EXISTANTE POUR FINALISER LA CRÉANCE EN COURS) ...
        $creance_id = $_SESSION['current_creance_id'] ?? null;

        if (!$creance_id) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => "Aucune créance en cours à finaliser."];
            header('Location: ../views/creances.php');
            exit();
        }
        
        // La créance reste à statut=0 (impayée) pour l'historique
        unset($_SESSION['current_creance_id']); 
        $_SESSION['message'] = ['type' => 'success', 'text' => "La Créance #{$creance_id} a été finalisée et est en attente de paiement. Vous pouvez la consulter dans l'historique."];
        header('Location: ../views/creances.php');
        exit();
    
    } elseif ($action === 'record_payment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // --- NOUVEAU: ENREGISTRER UN PAIEMENT ---
        $creance_id = $_POST['creance_id'] ?? null;
        $montant = floatval($_POST['montant'] ?? 0);
        $date_paiement = $_POST['date_paiement'] ?? date('Y-m-d');

        if (!$creance_id || $montant <= 0) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => "Montant de paiement ou ID de créance invalide."];
            header('Location: ../views/creance_details.php?id='.$creance_id);
            exit();
        }

        // 1. Enregistrer le paiement dans la table `paiements_creance`
        $req_insert_paiement = $connexion->prepare("
            INSERT INTO `paiements_creance` (`creance_id`, `montant`, `date_paiement`) 
            VALUES (:creance_id, :montant, :date_paiement)
        ");
        $req_insert_paiement->execute(['creance_id' => $creance_id, 'montant' => $montant, 'date_paiement' => $date_paiement]);
        
        // 2. Vérifier si la créance est soldée et mettre à jour le statut dans la table `creance`
        // Calculer le total dû
        $req_total_du = $connexion->prepare("SELECT COALESCE(SUM(quantite * prix), 0) as total_du FROM `ligne_creance` WHERE creance = :id");
        $req_total_du->execute(['id' => $creance_id]);
        $total_du = $req_total_du->fetchColumn();

        // Calculer le total payé
        $req_total_paye = $connexion->prepare("SELECT COALESCE(SUM(montant), 0) as total_paye FROM `paiements_creance` WHERE creance_id = :id");
        $req_total_paye->execute(['id' => $creance_id]);
        $total_paye = $req_total_paye->fetchColumn();

        if (round($total_paye, 2) >= round($total_du, 2)) {
            $req_update_statut = $connexion->prepare("UPDATE `creance` SET statut = 1 WHERE id = :id"); // 1 = Soldé
            $req_update_statut->execute(['id' => $creance_id]);
            $_SESSION['message'] = ['type' => 'success', 'text' => "Paiement de $".number_format($montant, 2, ',', ' ')." enregistré. Créance #{$creance_id} est maintenant **SOLDÉE**!"];
        } else {
            $_SESSION['message'] = ['type' => 'success', 'text' => "Paiement de $".number_format($montant, 2, ',', ' ')." enregistré. Reste à payer: $".number_format(($total_du - $total_paye), 2, ',', ' ')];
        }
        
        header('Location: ../views/creance_details.php?id='.$creance_id);
        exit();

    } elseif ($action === 'mark_paid' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        // --- NOUVEAU: MARQUER COMME SOLDÉ FORCÉMENT ---
        $creance_id = $_GET['id'] ?? null;
        
        if (!$creance_id) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => "ID de créance manquant."];
            header('Location: ../views/creances.php');
            exit();
        }

        $req_update_statut = $connexion->prepare("UPDATE `creance` SET statut = 1 WHERE id = :id"); // 1 = Soldé
        $req_update_statut->execute(['id' => $creance_id]);
        
        $_SESSION['message'] = ['type' => 'info', 'text' => "Créance #{$creance_id} marquée manuellement comme **SOLDÉE**."];
        header('Location: ../views/creance_details.php?id='.$creance_id);
        exit();

    } else {
        $_SESSION['message'] = ['type' => 'danger', 'text' => "Action non valide."];
        header('Location: ../views/creances.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => "Erreur DB: " . $e->getMessage()];
    // Redirige vers la page d'où vient la requête
    $redirect_page = (isset($_POST['creance_id']) || isset($_GET['id'])) ? '../views/creance_details.php?id='.($creance_id ?? $_POST['creance_id']) : '../views/creances.php';
    header('Location: '.$redirect_page);
    exit();
}
?>