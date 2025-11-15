<?php
session_start();
// Protection de la page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

include_once 'db_connect.php';

$message = '';
$action = $_GET['action'] ?? '';
$lecteur_id = (int)($_GET['id'] ?? 0);
$current_lecteur = [];

// --- GESTION DES REQUÊTES POST (CREATE, UPDATE, DELETE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_post = $_POST['action'] ?? '';

    // Récupération sécurisée et filtrée des données du formulaire
    $nom = filter_var($_POST['nom'] ?? '', FILTER_SANITIZE_STRING);
    $prenom = filter_var($_POST['prenom'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $id_post = (int)($_POST['id'] ?? 0);

    try {
        if ($action_post === 'create') {
            // C: CREATE
            $stmt = $pdo->prepare(
                "INSERT INTO lecteurs (nom, prenom, email) VALUES (:nom, :prenom, :email)"
            );
            $stmt->execute(compact('nom', 'prenom', 'email'));
            $message = "<p class='success'>✅ Lecteur **" . htmlspecialchars($prenom) . " " . htmlspecialchars($nom) . "** ajouté !</p>";
        
        } elseif ($action_post === 'update' && $id_post > 0) {
            // U: UPDATE (CORRECTION DE L'ERREUR SQLSTATE[HY093] !)
            $stmt = $pdo->prepare(
                "UPDATE lecteurs SET nom = :nom, prenom = :prenom, email = :email 
                 WHERE id = :id"
            );
            
            // Lier TOUS les paramètres y compris l'ID pour l'exécution
            $stmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':id' => $id_post
            ]);
            $message = "<p class='success'>🔄 Informations du lecteur **" . htmlspecialchars($prenom) . " " . htmlspecialchars($nom) . "** mises à jour !</p>";


        } elseif ($action_post === 'delete' && $id_post > 0) {
            // D: DELETE
            $stmt = $pdo->prepare("DELETE FROM lecteurs WHERE id = :id");
            $stmt->bindParam(':id', $id_post, PDO::PARAM_INT);
            $stmt->execute();
            $message = "<p class='success'>🗑️ Lecteur (ID: $id_post) supprimé.</p>";
        }

    } catch (PDOException $e) {
        $message = "<p class='error'>⚠️ Erreur BDD : " . $e->getMessage() . "</p>";
    }
}

// --- GESTION DES REQUÊTES GET (READ) ---
if ($action === 'edit' && $lecteur_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM lecteurs WHERE id = :id");
    $stmt->bindParam(':id', $lecteur_id, PDO::PARAM_INT);
    $stmt->execute();
    $current_lecteur = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$current_lecteur) {
        $message = "<p class='error'>Lecteur à modifier non trouvé.</p>";
        $action = '';
    }
}

// CORRECTION DU TRI
$stmt = $pdo->query("SELECT id, nom, prenom, email FROM lecteurs ORDER BY id ASC");
$lecteurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Lecteurs - St Augustin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Gestion des Lecteurs</h1>
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
            <h2><?= ($action === 'edit' && $current_lecteur) ? 'Modifier le Lecteur #' . $current_lecteur['id'] : '➕ Enregistrer un Nouveau Lecteur' ?></h2>
            <div style="max-width: 350px;">
                <form action="lecteurs.php" method="POST">
                    
                    <input type="hidden" name="action" value="<?= ($action === 'edit' && $current_lecteur) ? 'update' : 'create' ?>">
                    <?php if ($action === 'edit' && $current_lecteur): ?>
                        <input type="hidden" name="id" value="<?= $current_lecteur['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group"><label for="prenom">Prénom :</label>
                        <input type="text" id="prenom" name="prenom" required 
                            value="<?= htmlspecialchars($current_lecteur['prenom'] ?? '') ?>"></div>

                    <div class="form-group"><label for="nom">Nom :</label>
                        <input type="text" id="nom" name="nom" required
                            value="<?= htmlspecialchars($current_lecteur['nom'] ?? '') ?>"></div>

                    <div class="form-group"><label for="email">Email :</label>
                        <input type="email" id="email" name="email" required
                            value="<?= htmlspecialchars($current_lecteur['email'] ?? '') ?>"></div>
                    
                    <div style="text-align: right; width: 100%;">
                        <button type="submit"><?= ($action === 'edit' && $current_lecteur) ? 'Sauvegarder les Modifications' : 'Enregistrer le Lecteur' ?></button>
                        <?php if ($action === 'edit'): ?>
                            <a href="lecteurs.php" class="button back-link" style="background-color:#ccc; color:#333;">Annuler la modification</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>

        <section class="admin-section">
            <h2>Liste des Lecteurs Inscrits (<?= count($lecteurs) ?>) 

</h2>
            <table class="admin-table">
                <thead><tr><th>ID</th><th>Nom Complet</th><th>Email</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($lecteurs as $lecteur): ?>
                    <tr>
                        <td><?= $lecteur['id'] ?></td>
                        <td><?= htmlspecialchars($lecteur['prenom']) ?> <?= htmlspecialchars($lecteur['nom']) ?></td>
                        <td><?= htmlspecialchars($lecteur['email']) ?></td>
                        <td class="action-btns">
                            <a href="lecteurs.php?action=edit&id=<?= $lecteur['id'] ?>">✏️ Modifier</a>
                            <form action="lecteurs.php" method="POST" style="display:inline;" 
                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer <?= htmlspecialchars($lecteur['prenom']) ?>?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $lecteur['id'] ?>">
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