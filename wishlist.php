<?php
session_start();
include_once 'db_connect.php';

$message = '';
$id_lecteur = CURRENT_USER_ID; // Utilisateur simulé

// --- Suppression de la Liste (DELETE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_wishlist') {
    $id_livre_to_remove = (int)$_POST['livre_id'];
    $date_emprunt = $_POST['date_emprunt']; // Clé composite

    try {
        $stmt_delete = $pdo->prepare(
            "DELETE FROM liste_lecture WHERE id_livre = :id_livre AND id_lecteur = :id_lecteur AND date_emprunt = :date_emprunt"
        );
        $stmt_delete->execute([
            ':id_livre' => $id_livre_to_remove, 
            ':id_lecteur' => $id_lecteur,
            ':date_emprunt' => $date_emprunt
        ]);
        $message = "<p class='success'>🗑️ Livre retiré de votre liste de lecture.</p>";
    } catch (PDOException $e) {
        $message = "<p class='error'>⚠️ Erreur lors du retrait du livre.</p>";
    }
}

// --- Récupération de la Liste de Lecture (READ) ---
$wishlist = [];
$stmt = $pdo->prepare(
    "SELECT l.id, l.titre, l.auteur, ll.date_emprunt 
     FROM liste_lecture ll 
     JOIN livres l ON ll.id_livre = l.id 
     WHERE ll.id_lecteur = :id_lecteur" // Filtré par l'ID du lecteur simulé
);
$stmt->bindParam(':id_lecteur', $id_lecteur, PDO::PARAM_INT);
$stmt->execute();
$wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ma Liste de Lecture - St Augustin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Ma Liste de Lecture</h1>
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
        <h2>Livres que je souhaite dévorer :</h2>
        <?= $message ?>

        <div class="wishlist-items">
            <?php if (!empty($wishlist)): ?>
                <?php foreach ($wishlist as $item): ?>
                    <article class="wishlist-item admin-section">
                        <div>
                            <h3><a href="details.php?id=<?= $item['id'] ?>"><?= htmlspecialchars($item['titre']) ?></a></h3>
                            <p><strong>Auteur :</strong> <?= htmlspecialchars($item['auteur']) ?></p>
                            <p>Ajouté le : <?= date('d/m/Y', strtotime($item['date_emprunt'])) ?></p>
                        </div>
                        <form action="wishlist.php" method="POST"> 
                            <input type="hidden" name="livre_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="date_emprunt" value="<?= $item['date_emprunt'] ?>">
                            <input type="hidden" name="action" value="remove_wishlist">
                            <button type="submit" class="remove-btn">🗑️ Retirer</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Votre liste de lecture est vide. <a href="index.php">Commencez votre recherche !</a></p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 Bibliothèque St Augustin. Le savoir est une quête.</p>
    </footer>
</body>
</html>