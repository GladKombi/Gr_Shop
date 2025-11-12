<?php
# Inclusion du fichier de connexion à la base de données
include('../connexion/connexion.php');

# Vérifie si des données ont été envoyées via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    # AJOUT ou MODIFICATION d'une catégorie
    if (isset($_POST['Valider'])) {
        $description = $_POST['description'];
        $id = isset($_POST['id']) ? $_POST['id'] : null;

        if (empty($id)) {
            # AJOUT : insertion d'une nouvelle catégorie
            $sql = "INSERT INTO `categories` (`description`, `statut`) VALUES (:description, 0)";
            $action = 'ajoutée';
        } else {
            # MODIFICATION : mise à jour d'une catégorie existante
            $sql = "UPDATE `categories` SET `description` = :description WHERE `id` = :id";
            $action = 'modifiée';
        }

        try {
            $req = $connexion->prepare($sql);
            $req->bindValue(':description', $description);
            if (!empty($id)) {
                $req->bindValue(':id', $id);
            }
            $req->execute();
            
            # Ajouter un message de succès à la session
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'La catégorie a été ' . $action . ' avec succès.'
            ];

        } catch (PDOException $e) {
            # Ajouter un message d'erreur à la session
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Erreur lors de l\'enregistrement de la catégorie. Veuillez réessayer.'
            ];
        }
    }

    # SUPPRESSION d'une catégorie
    if (isset($_POST['delete_id'])) {
        $id = $_POST['delete_id'];
        $sql = "UPDATE `categories` SET `statut` = 1 WHERE `id` = :id";
        
        try {
            $req = $connexion->prepare($sql);
            $req->bindValue(':id', $id);
            $req->execute();
            
            # Ajouter un message de succès à la session
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'La catégorie a été supprimée avec succès.'
            ];

        } catch (PDOException $e) {
            # Ajouter un message d'erreur à la session
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Erreur lors de la suppression de la catégorie. Elle pourrait être liée à un produit.'
            ];
        }
    }
}

# Redirection vers la page des catégories après toutes les actions
header('Location: ../views/categories.php');
exit();
?>