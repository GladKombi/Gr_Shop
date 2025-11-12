<?php
# Connexion à la Db
include('../connexion/connexion.php');

// Définir les variables de configuration
$redirect_url = '../views/creanciers.php'; // URL vers l'interface de gestion après traitement
$upload_dir = '../img/'; // Chemin de destination pour le téléchargement des photos
$matricule_prefix = "G_Shop";
$matricule_length = 3; // Longueur du suffixe numérique (ex: 001, 010)

// --- FONCTION UTILITAIRE : REDIRECTION ET MESSAGES ---
function set_message_and_redirect($msg, $type, $url)
{
    $_SESSION['message'] = $msg;
    $_SESSION['message_type'] = $type;
    header("Location: " . $url);
    exit();
}

// --- FONCTION CLÉ : GÉNÉRATION DU MATRICULE ---
/**
 * Génère un nouveau matricule séquentiel (ex: G_Shop001) en fonction du dernier enregistrement.
 * @param PDO $connexion La connexion à la base de données.
 * @return string Le nouveau matricule généré.
 */
function generer_matricule(PDO $connexion, $prefix, $length): string
{
    // 1. Récupérer le dernier matricule
    $stmt = $connexion->prepare("SELECT matricule FROM creancier WHERE matricule LIKE ? ORDER BY CAST(SUBSTRING_INDEX(matricule, ?, -1) AS UNSIGNED) DESC LIMIT 1");
    $stmt->execute([$prefix . '%', $prefix]);
    $last_matricule = $stmt->fetchColumn();
    $next_number = 1;

    if ($last_matricule) {
        // 2. Extraire le numéro et incrémenter
        $number_part = substr($last_matricule, strlen($prefix));
        $current_number = (int)$number_part;
        $next_number = $current_number + 1;
    }

    // 3. Formater le nouveau numéro avec des zéros de tête
    $new_suffixe = str_pad($next_number, $length, '0', STR_PAD_LEFT);

    return $prefix . $new_suffixe;
}


// ==========================================================
// 2. GESTION DE LA SUPPRESSION (DELETE)
// ==========================================================
if (isset($_POST['delete_matricule'])) {
    $matricule_a_supprimer = $_POST['delete_matricule'];

    try {
        // Optionnel: Supprimer la photo associée
        $stmt_select = $connexion->prepare("SELECT photo FROM creancier WHERE matricule = ?");
        $stmt_select->execute([$matricule_a_supprimer]);
        $photo_name = $stmt_select->fetchColumn();

        if ($photo_name && file_exists($upload_dir . $photo_name)) {
            unlink($upload_dir . $photo_name); // Supprime le fichier physique
        }

        // Requête de suppression
        $stmt_delete = $connexion->prepare("DELETE FROM creancier WHERE matricule = ?");
        $stmt_delete->execute([$matricule_a_supprimer]);

        set_message_and_redirect("Le créancier (Matricule: $matricule_a_supprimer) a été supprimé avec succès.", 'success', $redirect_url);
    } catch (PDOException $e) {
        set_message_and_redirect("Erreur lors de la suppression du créancier : " . $e->getMessage(), 'error', $redirect_url);
    }
}


// ==========================================================
// 3. GESTION DE L'AJOUT ET DE LA MODIFICATION (CREATE / UPDATE)
// ==========================================================
if (isset($_POST['Valider'])) {

    // Récupération et Nettoyage des données du formulaire
    $nom = htmlspecialchars(trim($_POST['nom']));
    $postnom = htmlspecialchars(trim($_POST['postnom'] ?? ''));
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $telephone = htmlspecialchars(trim($_POST['telephone']));
    $statut = htmlspecialchars(trim($_POST['statut']));

    // old_matricule permet de savoir si on est en mode MODIFICATION
    $old_matricule = htmlspecialchars(trim($_POST['old_matricule'] ?? ''));

    // --- Gestion de l'image ---
    $new_photo_name = null;
    $current_photo_name = htmlspecialchars(trim($_POST['current_photo_name'] ?? ''));

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['photo']['tmp_name'];
        $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);

        // Le nom du fichier sera basé sur l'ancien matricule (pour update) ou une valeur temporaire (pour insert)
        $base_name = !empty($old_matricule) ? $old_matricule : 'new';

        $new_photo_name = strtolower($base_name) . '_' . time() . '.' . $file_extension;
        $file_destination = $upload_dir . $new_photo_name;

        // Déplacer le fichier téléchargé
        if (!move_uploaded_file($file_tmp_name, $file_destination)) {
            set_message_and_redirect("Erreur: Échec du téléchargement de l'image.", 'error', $redirect_url);
        }

        // Si c'est une MODIFICATION et qu'une nouvelle photo est là, supprimer l'ancienne
        if (!empty($old_matricule) && !empty($current_photo_name) && file_exists($upload_dir . $current_photo_name)) {
            unlink($upload_dir . $current_photo_name);
        }
    } else {
        // Conserver l'ancienne photo si pas de nouveau fichier
        $new_photo_name = $current_photo_name;
    }
    // --- Fin de gestion de l'image ---


    // ------------------------------------
    // DÉCISION : AJOUT (INSERT) ou MODIFICATION (UPDATE)
    // ------------------------------------
    if (empty($old_matricule)) {

        // --- MODE AJOUT (INSERT) ---
        try {
            // ** GENERATION AUTOMATIQUE DU MATRICULE **
            $matricule_genere = generer_matricule($connexion, $matricule_prefix, $matricule_length);

            $sql_insert = "INSERT INTO creancier (matricule, nom, postnom, prenom, telephone, photo, statut) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt_insert = $connexion->prepare($sql_insert);
            $stmt_insert->execute([$matricule_genere, $nom, $postnom, $prenom, $telephone, $new_photo_name, $statut]);

            set_message_and_redirect("Le nouveau créancier a été enregistré sous le matricule **$matricule_genere**.", 'success', $redirect_url);
        } catch (PDOException $e) {
            // En cas d'erreur connex$connexion, supprimer la photo téléchargée si elle existe
            if ($new_photo_name && $new_photo_name != $current_photo_name && file_exists($upload_dir . $new_photo_name)) {
                unlink($upload_dir . $new_photo_name);
            }
            set_message_and_redirect("Erreur lors de l'ajout : " . $e->getMessage(), 'error', $redirect_url);
        }
    } else {

        // --- MODE MODIFICATION (UPDATE) ---
        try {
            // Le matricule est l'ancien matricule stocké
            $matricule_a_modifier = $old_matricule;

            $sql_update = "UPDATE creancier SET nom = ?, postnom = ?, prenom = ?, telephone = ?, photo = ?, statut = ?
                           WHERE matricule = ?";

            $stmt_update = $connexion->prepare($sql_update);
            $stmt_update->execute([$nom, $postnom, $prenom, $telephone, $new_photo_name, $statut, $matricule_a_modifier]);

            set_message_and_redirect("Le créancier **$matricule_a_modifier** a été mis à jour avec succès.", 'success', $redirect_url);
        } catch (PDOException $e) {
            // En cas d'erreur connex$connexion, supprimer la photo téléchargée si elle existe
            if ($new_photo_name && $new_photo_name != $current_photo_name && file_exists($upload_dir . $new_photo_name)) {
                unlink($upload_dir . $new_photo_name);
            }
            set_message_and_redirect("Erreur lors de la mise à jour : " . $e->getMessage(), 'error', $redirect_url);
        }
    }
}

// Si le script est accédé directement sans action POST valide
set_message_and_redirect("Accès invalide à la page de traitement.", 'error', $redirect_url);
