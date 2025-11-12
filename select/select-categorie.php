<?php

$categories = [];
try {
    // Récupère toutes les catégories non archivées (si vous en avez besoin)
    $req_aff = $connexion->prepare("SELECT * FROM `categories` where statut=0 ORDER BY id DESC");
    $req_aff->execute();
    $categories = $req_aff->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En cas d'erreur de connexion, une liste vide sera utilisée
}
