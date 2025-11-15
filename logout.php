<?php
session_start(); 

// 1. Vider le tableau des variables de session
$_SESSION = array();

// 2. Supprimer le cookie de session (nettoyage complet et sécurisé)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Détruire la session
session_destroy();

// 4. Redirection vers l'accueil
header('Location: index.php');
exit;
?>