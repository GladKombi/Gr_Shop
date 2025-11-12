<?php
session_start();
include('../connexion/connexion.php');

// Vérifie s'il y a une commande en cours
if (!isset($_SESSION['current_commande_id'])) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Veuillez démarrer une nouvelle commande avant d\'ajouter des produits.'];
    header('Location: ../views/ventes.php');
    exit();
}

$commande_id = $_SESSION['current_commande_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Valider'])) {
    
    // Assurez-vous que les données sont valides et converties
    $id_produit = (int)$_POST['id_produit'];
    $quantite = (int)$_POST['quantite'];
    $prix_total_saisi = floatval($_POST['prix_total']); 
    $date = date('Y-m-d H:i:s');
    
    // Vérification de base
    if ($id_produit <= 0 || $quantite <= 0 || $prix_total_saisi <= 0) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Données de formulaire invalides.'];
        header('Location: ../views/ventes.php');
        exit();
    }

    try {
        $connexion->beginTransaction();

        // 1. Récupère le prix unitaire et le stock initial
        $req_product = $connexion->prepare("SELECT nom, prix AS prix_unitaire, quantite_en_stock FROM `produits` WHERE id = :id");
        $req_product->execute(['id' => $id_produit]);
        $product = $req_product->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception('Produit introuvable ou requête de stock/prix échouée.');
        }

        $nom_produit = $product['nom'];
        $prix_unitaire_db = floatval($product['prix_unitaire']);
        $stock_total_initial = (int)$product['quantite_en_stock']; // Stock total dans la DB

        // 2. Calcule le stock VENDU (excluant les produits de la commande EN COURS)
        $req_stock = $connexion->prepare("SELECT COALESCE(SUM(quantite), 0) AS total_vendu FROM `ligne_commande` WHERE produit_id = :id_produit AND commande_id != :commande_id_actuelle   ");
        $req_stock->execute([
            'id_produit' => $id_produit,
            'commande_id_actuelle' => $commande_id
        ]);
        $stocks_V = $req_stock->fetch(PDO::FETCH_ASSOC);
        $stock_vendu_passe = (int)$stocks_V['total_vendu'];
        
        // 3. Calcule le stock déjà mis au panier dans la COMMANDE ACTUELLE (pour le même produit)
        $req_stock_panier = $connexion->prepare("SELECT COALESCE(SUM(quantite), 0) AS total_panier FROM `ligne_commande` WHERE produit_id = :id_produit AND commande_id = :commande_id_actuelle  ");
        $req_stock_panier->execute([
            'id_produit' => $id_produit,
            'commande_id_actuelle' => $commande_id
        ]);
        $stocks_P = $req_stock_panier->fetch(PDO::FETCH_ASSOC);
        $stock_en_cours_panier = (int)$stocks_P['total_panier'];

        // 4. Calcul du Stock Disponible réel
        // C'est le stock initial moins TOUT ce qui est vendu dans les autres commandes.
        $stock_disponible_reel = $stock_total_initial - $stock_vendu_passe;

        // 5. Calcul du stock qui sera restant APRES la nouvelle ligne de commande
        // Le stock disponible réel doit être supérieur à la quantité totale dans le panier,
        // c'est-à-dire : (stock déjà mis au panier + nouvelle quantité) <= stock_disponible_reel
        $stock_apres_ajout = $stock_disponible_reel - ($stock_en_cours_panier + $quantite);

        // --- VÉRIFICATION DE PRIX ET DE STOCK ---
        
        $prix_total_calcule = round($prix_unitaire_db * $quantite, 2);
        
        // Vérification de Prix (Reste inchangée et valide)
        if ($prix_total_saisi < ($prix_total_calcule - 0.01)) {
             $_SESSION['message'] = [
                 'type' => 'danger', 
                 'text' => "Erreur de prix. Le prix saisi est trop bas. Prix attendu : " . number_format($prix_total_calcule, 2) . " $"
             ];
             $connexion->rollBack();
             header('Location: ../views/ventes.php');
             exit();
        }
        
        // VÉRIFICATION DE STOCK (Utilisation de la nouvelle logique)
        if ($stock_apres_ajout < 0) {
            $stock_restant_possible = $stock_disponible_reel - $stock_en_cours_panier;
            $_SESSION['message'] = [
                'type' => 'danger', 
                'text' => 'Stock insuffisant pour ce produit. Vous avez déjà ' . $stock_en_cours_panier . ' au panier. Quantité restante possible à ajouter : ' . $stock_restant_possible
            ];
            $connexion->rollBack();
            header('Location: ../views/ventes.php');
            exit();
        }

        // 6. Enregistre la LIGNE DE COMMANDE (Aucune mise à jour du stock dans la table produits ici)
        $prix_total_final = $prix_total_calcule; 
        
        // Suppression du champ 'produit' si non nécessaire
        $sql_insert_line = "INSERT INTO `ligne_commande` (`commande_id`, `produit_id`, `quantite`, `prix_total`, `statut`) VALUES (:commande_id, :produit_id, :quantite, :prix_total, 0)";
        $req_insert_line = $connexion->prepare($sql_insert_line);
        $req_insert_line->bindValue(':commande_id', $commande_id); 
        $req_insert_line->bindValue(':produit_id', $id_produit);
        $req_insert_line->bindValue(':quantite', $quantite);
        $req_insert_line->bindValue(':prix_total', $prix_total_final); 
        $req_insert_line->execute();
        
        $connexion->commit();
        
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Produit ajouté au panier avec succès. Stock disponible restant: ' . $stock_apres_ajout];
        
    } catch (PDOException $e) {
        $connexion->rollBack();
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Erreur DB: ' . $e->getMessage()];
    } catch (Exception $e) {
        $connexion->rollBack();
        $_SESSION['message'] = ['type' => 'danger', 'text' => $e->getMessage()];
    }
}

// Redirection vers la page des ventes après toutes les actions
header('Location: ../views/ventes.php');
exit();
?>