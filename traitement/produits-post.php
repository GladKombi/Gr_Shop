<?php
// Inclusion du fichier de connexion à la base de données
include('../connexion/connexion.php');

// Définir le répertoire de destination des images
// Assurez-vous que ce dossier existe et est accessible en écriture par le serveur web
$upload_dir = '../uploads/produits/';

// Vérifie si des données ont été envoyées via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // AJOUT ou MODIFICATION d'un produit
    if (isset($_POST['Valider'])) {
        $nom = $_POST['nom'];
        $categorie = $_POST['categorie'];
        $prix = $_POST['prix'];
        $quantite_en_stock = $_POST['quantite_en_stock'];
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        
        // Récupérer le chemin de la photo existante lors de la modification (champ caché)
        $current_photo_path = isset($_POST['current_photo_path']) ? $_POST['current_photo_path'] : '';
        $photo_db_path = $current_photo_path; // Par défaut, on garde l'ancienne photo

        // Validation des données de base
        if (empty($nom) || empty($categorie) || empty($prix) || $quantite_en_stock === '') {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Tous les champs (Nom, Catégorie, Prix, Stock) sont obligatoires.'
            ];
            header('Location: ../views/produits.php');
            exit();
        }

        // --- GESTION DE L'UPLOAD DE FICHIER PHOTO ---
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['photo']['tmp_name'];
            $file_name = $_FILES['photo']['name'];
            $file_size = $_FILES['photo']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validation de base de la photo
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 5 * 1024 * 1024; // 5 MB

            if (!in_array($file_ext, $allowed_extensions)) {
                $_SESSION['message'] = ['type' => 'danger', 'text' => 'Type de fichier non autorisé. Utilisez JPG, JPEG, PNG ou GIF.'];
                header('Location: ../views/produits.php');
                exit();
            }
            if ($file_size > $max_size) {
                $_SESSION['message'] = ['type' => 'danger', 'text' => 'Le fichier est trop volumineux (max 5 Mo).'];
                header('Location: ../views/produits.php');
                exit();
            }

            // Générer un nom de fichier unique et sécurisé
            $new_file_name = uniqid('G_Shop', true) . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;
            
            // Tenter de déplacer le fichier
            if (move_uploaded_file($file_tmp, $destination)) {
                $photo_db_path = str_replace('../img', '', $destination); // Chemin à enregistrer en DB (ex: uploads/produits/...)
                
                // Optionnel: Supprimer l'ancienne photo lors de la modification si une nouvelle a été uploadée
                if (!empty($current_photo_path) && file_exists('../img' . $current_photo_path)) {
                    unlink('../img' . $current_photo_path);
                }
            } else {
                $_SESSION['message'] = ['type' => 'danger', 'text' => 'Erreur lors du déplacement du fichier photo.'];
                header('Location: ../views/produits.php');
                exit();
            }
        }
        // --- FIN GESTION PHOTO ---


        if (empty($id)) {
            // AJOUT : insertion d'un nouveau produit
            $date = date('Y-m-d H:i:s');
            // MODIFICATION DE LA REQUÊTE INSERT
            $sql = "INSERT INTO `produits` (`date`, `nom`, `categorie`, `prix`, `quantite_en_stock`, `photo`, `statut`) 
                    VALUES (:date, :nom, :categorie, :prix, :quantite_en_stock, :photo, 0)"; // Statut 1 (actif) par défaut pour un nouvel ajout
            $action = 'ajouté';
        } else {
            // MODIFICATION : mise à jour d'un produit existant
            // La photo est mise à jour SEULEMENT si $photo_db_path a changé
            if ($photo_db_path !== $current_photo_path) {
                $photo_update_sql = ", `photo` = :photo";
            } else {
                $photo_update_sql = "";
            }

            // MODIFICATION DE LA REQUÊTE UPDATE
            $sql = "UPDATE `produits` 
                    SET `nom` = :nom, `categorie` = :categorie, `prix` = :prix, `quantite_en_stock` = :quantite_en_stock {$photo_update_sql} 
                    WHERE `id` = :id";
            $action = 'modifié';
        }

        try {
            $req = $connexion->prepare($sql);
            $req->bindValue(':nom', $nom);
            $req->bindValue(':categorie', $categorie);
            $req->bindValue(':prix', $prix);
            $req->bindValue(':quantite_en_stock', $quantite_en_stock);
            
            // Lier le paramètre photo si nécessaire
            if (empty($id) || $photo_db_path !== $current_photo_path) {
                $req->bindValue(':photo', $photo_db_path);
            }
            
            if (empty($id)) {
                $req->bindValue(':date', $date);
            } else {
                $req->bindValue(':id', $id);
            }
            
            $req->execute();

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Le produit a été ' . $action . ' avec succès.'
            ];

        } catch (PDOException $e) {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Erreur lors de l\'enregistrement du produit. Détail: ' . $e->getMessage()
            ];
        }
    }

    // Le code de suppression reste inchangé (utilise statut=0, souvent utilisé pour la suppression logique)
    if (isset($_POST['delete_id'])) {
        $id = $_POST['delete_id'];
        // Supposons que statut = 0 signifie 'supprimé/inactif'
        $sql = "UPDATE `produits` SET `statut` = 0 WHERE `id` = :id"; 

        try {
            $req = $connexion->prepare($sql);
            $req->bindValue(':id', $id);
            $req->execute();

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Le produit a été supprimé avec succès.'
            ];

        } catch (PDOException $e) {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Erreur lors de la suppression du produit.'
            ];
        }
    }
}

// Redirection vers la page des produits après toutes les actions
header('Location: ../views/produits.php');
exit();
?>