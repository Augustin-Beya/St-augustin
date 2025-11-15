<?php 
// Démarrage de la session pour vérifier le statut de connexion
session_start();
// Inclusion du fichier de connexion (une seule fois)
include_once 'db_connect.php'; 

// Récupérer le nombre total de livres
$stmt_total_livres = $pdo->query("SELECT COUNT(*) FROM livres");
$total_livres = $stmt_total_livres->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bienvenue à la Bibliothèque St Augustin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Bibliothèque St Augustin</h1>
        <nav>
            <a href="index.php">Accueil</a>
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <a href="admin.php">Gestion Livres</a>
                <a href="lecteurs.php">Gestion Lecteurs</a>
                <a href="emprunts.php">Gestion Emprunts</a>
                <a href="logout.php" style="background-color: #A9A9A9;">Déconnexion (<?= $_SESSION['username'] ?>)</a>
            <?php else: ?>
                <a href="login.php">Connexion Admin</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        
        <section class="presentation hero-banner admin-section">
            <h2>📚 Le Savoir est un Trésor à Partager</h2>
            <p>Bienvenue sur le portail de la Bibliothèque St Augustin. Explorez notre catalogue !</p>
            <p style="font-size: 1.2em; margin-top: 15px;">Nous comptons plus de **<?= $total_livres ?>** ouvrages disponibles.</p>
        </section>

        <section class="search-form admin-section">
            <h2>🔎 Cherchez votre prochaine lecture</h2>
            <form action="results.php" method="GET">
                <input type="text" name="q" placeholder="Titre, auteur ou mot-clé..." required>
                <button type="submit">Rechercher</button>
            </form>
            
            <div class="quick-links">
                <p>💡 <a href="results.php?q=classique">Suggestions</a></p>
                <p>🆕 <a href="results.php?q=nouveauté">Nouveautés du mois</a></p>
                <p>❤️ Livres favoris de la communauté.</p>
            </div>
            
        </section>

    </main>

    <footer>
        <p>&copy; 2025 Bibliothèque St Augustin. Le savoir est une quête.</p>
    </footer>
</body>
</html>