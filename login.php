<?php
session_start();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Identifiants statiques pour la démo
    $username_attendu = 'admin';
    $password_attendu = 'augustin2025'; 

    $username_saisi = $_POST['username'] ?? '';
    $password_saisi = $_POST['password'] ?? '';

    // Vérification de l'authentification
    if ($username_saisi === $username_attendu && $password_saisi === $password_attendu) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username_attendu;
        header('Location: admin.php');
        exit;
    } else {
        $message = "<p class='error'>Identifiants incorrects. Veuillez réessayer.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion Administration - St Augustin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Connexion Administration</h1>
    </header>

    <main>
        <section class="admin-section" style="max-width: 400px; margin: 50px auto; text-align: center;">
            <h2>Accès Réservé </h2>
            <?= $message ?>
            <p style="margin-bottom: 20px; font-size: 0.9em; color: #555;">
                Utilisez : **admin** / **augustin2025**
            </p>
            <form action="login.php" method="POST">
                <div class="form-group"><label for="username">Nom d'utilisateur :</label>
                    <input type="text" id="username" name="username" required></div>
                <div class="form-group"><label for="password">Mot de passe :</label>
                    <input type="password" id="password" name="password" required></div>
                <button type="submit">Se connecter</button>
            </form>
            <p style="margin-top: 20px;"><a href="index.php">← Retour au catalogue public</a></p>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2025 Bibliothèque St Augustin.</p>
    </footer>
</body>
</html>