<?php
session_start();
include_once 'db_connect.php';

$livre = null;
$message = '';
$livre_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- Logique d'Ajout à la Liste de Lecture (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_wishlist') {
    $id_livre_to_add = (int)$_POST['livre_id'];
    $id_lecteur = CURRENT_USER_ID; 
    $date_emprunt = date('Y-m-d'); 

    try {
        // Enregistrement dans la table de liaison 'liste_lecture'
        $stmt_insert = $pdo->prepare(
            "INSERT INTO liste_lecture (id_livre, id_lecteur, date_emprunt) 
             VALUES (:id_livre, :id_lecteur, :date_emprunt)"
        );
        $stmt_insert->execute([
            ':id_livre' => $id_livre_to_add, 
            ':id_lecteur' => $id_lecteur, 
            ':date_emprunt' => $date_emprunt
        ]);
        $message = "<p class='success'>✅ Le livre a été ajouté à votre liste de lecture !</p>";
    } catch (PDOException $e) {
        $message = "<p class='error'>⚠️ Ce livre est déjà dans votre liste de lecture ou une erreur est survenue.</p>";
    }
}

// --- Récupération des Détails du Livre (READ) ---
if ($livre_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM livres WHERE id = :id");
    $stmt->bindParam(':id', $livre_id, PDO::PARAM_INT);
    $stmt->execute();
    $livre = $stmt->fetch(PDO::FETCH_ASSOC); 
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du Livre - St Augustin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Détails du Livre</h1>
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
        <?php if ($livre): ?>
            <section class="book-details admin-section">
                <?= $message // Affichage du message de succès/erreur ?>
                <h2><?= htmlspecialchars($livre['titre']) ?> 

</h2>
                <div class="details-content">
                    <div>
                        <p><strong>Auteur :</strong> <?= htmlspecialchars($livre['auteur']) ?></p>
                        <p><strong>Maison d'édition :</strong> <?= htmlspecialchars($livre['maison_edition']) ?></p>
                        <p><strong>Exemplaires en stock :</strong> <?= $livre['nombre_exemplaire'] ?></p>
                    </div>
                    <div class="description-block">
                        <h3>Description</h3>
                        <p><?= nl2br(htmlspecialchars($livre['description'])) ?></p>
                    </div>
                </div>

                <form action="details.php?id=<?= $livre_id ?>" method="POST"> 
                    <input type="hidden" name="livre_id" value="<?= $livre['id'] ?>">
                    <input type="hidden" name="action" value="add_wishlist">
                    <button type="submit" class="add-to-wishlist">➕ Ajouter à ma Liste de Lecture</button>
                </form>
                
                <a href="javascript:history.back()" class="back-link">← Retour aux résultats</a>
            </section>
        <?php else: ?>
            <h2>Livre non trouvé</h2>
            <p>Le livre demandé n'existe pas dans le catalogue.</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 Bibliothèque St Augustin. Le savoir est une quête.</p>
    </footer>
</body>
</html>