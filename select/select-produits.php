<?php
$products = [];
$categories = [];

try {
    $statut = 0;
    // Récupère tous les produits
    $req_products = $connexion->prepare("SELECT `produits`.*, categories.description FROM `produits`,categories WHERE produits.categorie=categories.id AND produits.statut = ? ORDER BY produits.id DESC");
    $req_products->execute([$statut]);
    $products = $req_products->fetchAll(PDO::FETCH_ASSOC);

    // Récupère toutes les catégories pour le menu déroulant
    $req_categories = $connexion->prepare("SELECT * FROM `categories` where statut = ? ORDER BY description ASC");
    $req_categories->execute([$statut]);
    $categories = $req_categories->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En cas d'erreur de connexion, les listes seront vides
}
