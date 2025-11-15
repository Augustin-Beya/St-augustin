<?php
session_start(); 
// Protection de la page : redirige vers login.php si non connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

include_once 'db_connect.php';

$message = '';
$action = $_GET['action'] ?? '';
$livre_id = (int)($_GET['id'] ?? 0);
$current_livre = [];

// --- GESTION DES REQUÊTES POST (CREATE, UPDATE, DELETE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_post = $_POST['action'] ?? '';

    // Récupération sécurisée et filtrée des données du formulaire
    $titre = filter_var($_POST['titre'] ?? '', FILTER_SANITIZE_STRING);
    $auteur = filter_var($_POST['auteur'] ?? '', FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['description'] ?? '', FILTER_SANITIZE_STRING);
    $maison_edition = filter_var($_POST['maison_edition'] ?? '', FILTER_SANITIZE_STRING);
    $nombre_exemplaire = (int)($_POST['nombre_exemplaire'] ?? 1);
    $id_post = (int)($_POST['id'] ?? 0);

    try {
        if ($action_post === 'create') {
            // C: CREATE
            $stmt = $pdo->prepare(
                "INSERT INTO livres (titre, auteur, description, maison_edition, nombre_exemplaire) 
                 VALUES (:titre, :auteur, :description, :maison_edition, :nombre_exemplaire)"
            );
            $stmt->execute(compact('titre', 'auteur', 'description', 'maison_edition', 'nombre_exemplaire'));
            $message = "<p class='success'>✅ Livre **" . htmlspecialchars($titre) . "** ajouté avec succès !</p>";
        
        } elseif ($action_post === 'update' && $id_post > 0) {
            // U: UPDATE (CORRECTION DE L'ERREUR SQLSTATE[HY093] !)
            $stmt = $pdo->prepare(
                "UPDATE livres SET titre = :titre, auteur = :auteur, description = :description, 
                 maison_edition = :maison_edition, nombre_exemplaire = :nombre_exemplaire 
                 WHERE id = :id"
            );
            
            // Lier TOUS les paramètres y compris l'ID pour l'exécution
            $stmt->execute([
                ':titre' => $titre,
                ':auteur' => $auteur,
                ':description' => $description,
                ':maison_edition' => $maison_edition,
                ':nombre_exemplaire' => $nombre_exemplaire,
                ':id' => $id_post 
            ]);
            $message = "<p class='success'>🔄 Livre **" . htmlspecialchars($titre) . "** mis à jour avec succès.</p>";

        } elseif ($action_post === 'delete' && $id_post > 0) {
            // D: DELETE
            $stmt = $pdo->prepare("DELETE FROM livres WHERE id = :id");
            $stmt->bindParam(':id', $id_post, PDO::PARAM_INT);
            $stmt->execute();
            $message = "<p class='success'>🗑️ Livre (ID: $id_post) supprimé du catalogue.</p>";
        }

    } catch (PDOException $e) {
        $message = "<p class='error'>⚠️ Erreur BDD : " . $e->getMessage() . "</p>";
    }
}

// --- GESTION DES REQUÊTES GET (READ) ---
if ($action === 'edit' && $livre_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM livres WHERE id = :id");
    $stmt->bindParam(':id', $livre_id, PDO::PARAM_INT);
    $stmt->execute();
    $current_livre = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$current_livre) {
        $message = "<p class='error'>Livre à modifier non trouvé.</p>";
        $action = '';
    }
}

// R: READ ALL (CORRECTION DU TRI : ORDER BY id ASC)
$stmt = $pdo->query("SELECT id, titre, auteur, nombre_exemplaire FROM livres ORDER BY id ASC");
$livres = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration du Catalogue - St Augustin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Gestion du Catalogue Livres</h1>
        <nav>
            <a href="index.php">Catalogue Public</a>
            <a href="admin.php">Gestion Livres</a>
            <a href="lecteurs.php">Gestion Lecteurs</a>
            <a href="emprunts.php">Gestion Emprunts</a>
            <a href="logout.php" style="background-color: #A9A9A9;">Déconnexion (<?= $_SESSION['username'] ?>)</a>
        </nav>
    </header>

    <main>
        <?= $message ?>
        
        <section class="admin-section">
            <h2><?= ($action === 'edit' && $current_livre) ? 'Modifier le Livre #' . $current_livre['id'] : 'Ajouter un Nouveau Livre' ?></h2>
            <div style="max-width: 350px;"> 
                <form action="admin.php" method="POST">
                    
                    <input type="hidden" name="action" value="<?= ($action === 'edit' && $current_livre) ? 'update' : 'create' ?>">
                    <?php if ($action === 'edit' && $current_livre): ?>
                        <input type="hidden" name="id" value="<?= $current_livre['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group"><label for="titre">Titre :</label>
                        <input type="text" id="titre" name="titre" required 
                            value="<?= htmlspecialchars($current_livre['titre'] ?? '') ?>"></div>

                    <div class="form-group"><label for="auteur">Auteur :</label>
                        <input type="text" id="auteur" name="auteur" required 
                            value="<?= htmlspecialchars($current_livre['auteur'] ?? '') ?>"></div>

                    <div class="form-group"><label for="maison_edition">Maison d'édition :</label>
                        <input type="text" id="maison_edition" name="maison_edition" 
                            value="<?= htmlspecialchars($current_livre['maison_edition'] ?? '') ?>"></div>
                    
                    <div class="form-group"><label for="nombre_exemplaire">Nombre d'exemplaires :</label>
                        <input type="number" id="nombre_exemplaire" name="nombre_exemplaire" min="0" required 
                            value="<?= htmlspecialchars($current_livre['nombre_exemplaire'] ?? 1) ?>"></div>

                    <div class="form-group" style="max-width: 700px;"><label for="description">Description :</label>
                        <textarea id="description" name="description" rows="5"><?= htmlspecialchars($current_livre['description'] ?? '') ?></textarea></div>

                    <div style="text-align: right; width: 100%;">
                        <button type="submit"><?= ($action === 'edit' && $current_livre) ? 'Sauvegarder les Modifications' : 'Ajouter au Catalogue' ?></button>
                        <?php if ($action === 'edit'): ?>
                            <a href="admin.php" class="button back-link" style="background-color:#ccc; color:#333;">Annuler la modification</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>

        <section class="admin-section">
            <h2>Catalogue Actuel (<?= count($livres) ?> Livres) </h2>
            <table class="admin-table">
                <thead><tr><th>ID</th><th>Titre</th><th>Auteur</th><th>Exemplaires</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($livres as $livre): ?>
                    <tr>
                        <td><?= $livre['id'] ?></td>
                        <td><?= htmlspecialchars($livre['titre']) ?></td>
                        <td><?= htmlspecialchars($livre['auteur']) ?></td>
                        <td><?= $livre['nombre_exemplaire'] ?></td>
                        <td class="action-btns">
                            <a href="admin.php?action=edit&id=<?= $livre['id'] ?>">✏️ Modifier</a>
                            <form action="admin.php" method="POST" style="display:inline;" 
                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer <?= htmlspecialchars($livre['titre']) ?>?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $livre['id'] ?>">
                                <button type="submit" class="delete-btn">🗑️ Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Bibliothèque St Augustin. Le savoir est une quête.</p>
    </footer>
</body>
</html>