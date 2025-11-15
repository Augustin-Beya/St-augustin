<?php
session_start();
include_once 'db_connect.php';

// Récupération sécurisée de la requête de recherche 'q'
$search_query = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING) ?? '';
$livres = [];

if (!empty($search_query)) {
    try {
        // Préparation de la requête SQL (recherche partielle par Titre OU Auteur)
        $stmt = $pdo->prepare(
            "SELECT id, titre, auteur, nombre_exemplaire 
             FROM livres 
             WHERE titre LIKE :query OR auteur LIKE :query"
        );
        
        // Liaison des paramètres avec les jokers '%' pour la recherche approximative
        $search_param = '%' . $search_query . '%';
        $stmt->bindParam(':query', $search_param);
        
        $stmt->execute();
        $livres = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        die("Erreur lors de la recherche SQL : " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats de Recherche - St Augustin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Résultats de Recherche</h1>
        <nav>
            <a href="index.php">Accueil</a>
            <a href="wishlist.php">Ma Liste de Lecture</a>
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
        <h2>Résultats pour : "<?= htmlspecialchars($search_query) ?>"</h2>
        
        <div class="book-list">
            <?php if (!empty($livres)): ?>
                <?php foreach ($livres as $livre): ?>
                    <article class="book-card">
                        <h3><?= htmlspecialchars($livre['titre']) ?></h3>
                        <p><strong>Auteur :</strong> <?= htmlspecialchars($livre['auteur']) ?></p>
                        <p class="exemplaires">Disponibles : <?= $livre['nombre_exemplaire'] ?></p>
                        <a href="details.php?id=<?= $livre['id'] ?>">
                            <button>Voir les Détails</button>
                        </a>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-results">Désolé, aucun livre ne correspond à votre recherche.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 Bibliothèque St Augustin. Le savoir est une quête.</p>
    </footer>
</body>
</html>