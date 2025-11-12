<?php
// Paramètres de connexion
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "angel_tech";
#Demarer la session
session_start();
$error_message = "";
try {
	$connexion = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
	$connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	// En cas d'erreur de connexion ou de requête, stocker le message
	$error_message = "Erreur de connexion à la base de données : " . $e->getMessage();
}
