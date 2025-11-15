<?php
/**
 * Fichier de Connexion à la Base de Données (librarie_db)
 */

// --- CONFIGURATION DE LA BASE DE DONNÉES ---
$host = 'localhost';
$db   = 'librairie_db'; //Nom de la base de données
$user = 'root'; // Identifiant MySQL.
$pass = '';     // Mot de passe par défaut de MySQL

// Data Source Name (DSN) pour la connexion PDO
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
     // Création d'une nouvelle connexion PDO 
     $pdo = new PDO($dsn, $user, $pass);
     
     // PDO pour lever des exceptions en cas d'erreur SQL.
     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
     
     // Simulation d'un lecteur connecté pour les fonctionnalités publiques (liste de lecture).
     // Utilisation de defined() pour éviter l'avertissement 'already defined'
     if (!defined('CURRENT_USER_ID')) {
         define('CURRENT_USER_ID', 1); 
     }

} catch (PDOException $e) {
     // Affichage d'une erreur claire si la connexion échoue.
     die("
        <div style='background-color:#f8d7da; color:#721c24; padding:20px; border: 1px solid #f5c6cb;'>
            <strong>ERREUR DE CONNEXION À LA BASE DE DONNÉES :</strong><br>
            Vérifiez XAMPP et le fichier db_connect.php. Détails : {$e->getMessage()}
        </div>
     ");
}
?>