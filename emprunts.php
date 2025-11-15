<?php
session_start();
// Protection de la page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

include_once 'db_connect.php';

$message = '';
$today = date('Y-m-d'); // Date du jour pour les transactions

// --- GESTION DES REQUÊTES POST (EMPRUNTER et RETOURNER) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_post = $_POST['action'] ?? '';

    try {
        if ($action_post === 'emprunter') {
            // Enregistrer un Emprunt
            $id_lecteur = (int)$_POST['id_lecteur'];
            $id_livre = (int)$_POST['id_livre'];
            $date_emprunt = $today;
            
            // 1. Vérification du stock
            $stmt_stock = $pdo->prepare("SELECT nombre_exemplaire FROM livres WHERE id = ?");
            $stmt_stock->execute([$id_livre]);
            $stock = $stmt_stock->fetchColumn();

            if ($stock > 0) {
                // Enregistrer l'emprunt. date_retour est laissé à NULL (signifie en cours)
                $stmt = $pdo->prepare(
                    "INSERT INTO liste_lecture (id_livre, id_lecteur, date_emprunt, date_retour) 
                     VALUES (:id_livre, :id_lecteur, :date_emprunt, NULL)" 
                );
                $stmt->execute(compact('id_livre', 'id_lecteur', 'date_emprunt'));

                // 2. Décrémenter le stock
                $pdo->prepare("UPDATE livres SET nombre_exemplaire = nombre_exemplaire - 1 WHERE id = ?")
                    ->execute([$id_livre]);

                $date_prevue_display = date('d/m/Y', strtotime($date_emprunt . ' +30 days'));
                $message = "<p class='success'>✅ Emprunt enregistré ! Retour prévu le " . $date_prevue_display . "</p>";
            } else {
                 $message = "<p class='error'>❌ ERREUR : Le livre n'a plus d'exemplaires en stock.</p>";
            }


        } elseif ($action_post === 'retourner') {
            // U: UPDATE (Marquer le Retour)
            $id_livre_retour = (int)$_POST['id_livre_retour'];
            $id_lecteur_retour = (int)$_POST['id_lecteur_retour'];
            $date_emprunt_cle = $_POST['date_emprunt_cle']; 

            // 1. Mettre à jour la date_retour avec la date réelle de retour ($today)
            $stmt = $pdo->prepare(
                "UPDATE liste_lecture SET date_retour = :date_retour_reelle 
                 WHERE id_livre = :id_livre AND id_lecteur = :id_lecteur AND date_emprunt = :date_emprunt_cle"
            );
            $stmt->execute([
                ':date_retour_reelle' => $today,
                ':id_livre' => $id_livre_retour, 
                ':id_lecteur' => $id_lecteur_retour,
                ':date_emprunt_cle' => $date_emprunt_cle
            ]);

            // 2. Incrémenter le stock
            $pdo->prepare("UPDATE livres SET nombre_exemplaire = nombre_exemplaire + 1 WHERE id = ?")
                ->execute([$id_livre_retour]);
            
            $message = "<p class='success'>🔄 Livre marqué comme rendu le " . date('d/m/Y', strtotime($today)) . " ! Stock mis à jour.</p>";
        }

    } catch (PDOException $e) {
        $message = "<p class='error'>⚠️ Erreur BDD : " . $e->getMessage() . "</p>";
    }
}

// --- RÉCUPÉRATION DES DONNÉES ET TRAITEMENT ---

// Récupération des données pour les formulaires
$lecteurs = $pdo->query("SELECT id, nom, prenom FROM lecteurs ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$livres = $pdo->query("SELECT id, titre, auteur, nombre_exemplaire FROM livres ORDER BY titre")->fetchAll(PDO::FETCH_ASSOC);

// A. Emprunts en cours (date_retour IS NULL)
$stmt_emprunts = $pdo->query(
    "SELECT ll.id_livre, ll.id_lecteur, ll.date_emprunt, 
            l.titre, l.auteur, r.prenom, r.nom
     FROM liste_lecture ll
     JOIN livres l ON ll.id_livre = l.id
     JOIN lecteurs r ON ll.id_lecteur = r.id
     WHERE ll.date_retour IS NULL 
     ORDER BY ll.date_emprunt DESC"
);
$emprunts_en_cours = $stmt_emprunts->fetchAll(PDO::FETCH_ASSOC);

// Traitement pour l'affichage (Calcul de la Date Prévue et du Statut)
foreach ($emprunts_en_cours as $key => $emprunt) {
    $date_emprunt = $emprunt['date_emprunt'];
    $date_prevue = date('Y-m-d', strtotime($date_emprunt . ' +30 days')); // Règle: 30 jours
    
    $emprunts_en_cours[$key]['date_retour_prevue'] = $date_prevue;
    $emprunts_en_cours[$key]['en_retard'] = ($date_prevue < $today); // Vrai si la date prévue est passée
}

// B. Historique des Retours (date_retour IS NOT NULL)
$stmt_historique = $pdo->query(
    "SELECT ll.id_livre, ll.id_lecteur, ll.date_emprunt, ll.date_retour, 
            l.titre, l.auteur, r.prenom, r.nom
     FROM liste_lecture ll
     JOIN livres l ON ll.id_livre = l.id
     JOIN lecteurs r ON ll.id_lecteur = r.id
     WHERE ll.date_retour IS NOT NULL 
     ORDER BY ll.date_retour DESC"
);
$historique_rendu = $stmt_historique->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Emprunts - St Augustin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Gestion des Emprunts</h1>
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
            <h2>➕ Enregistrer un Nouvel Emprunt</h2>
            <form action="emprunts.php" method="POST" style="display:flex; flex-wrap: wrap; gap: 20px;">
                
                <input type="hidden" name="action" value="emprunter">

                <div class="form-group" style="flex: 1 1 30%;">
                    <label for="id_lecteur">Lecteur :</label>
                    <select id="id_lecteur" name="id_lecteur" required style="width: 100%; padding: 10px;">
                        <option value="">-- Choisir un lecteur --</option>
                        <?php foreach ($lecteurs as $lecteur): ?>
                            <option value="<?= $lecteur['id'] ?>">
                                <?= htmlspecialchars($lecteur['prenom']) ?> <?= htmlspecialchars($lecteur['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="flex: 1 1 50%;">
                    <label for="id_livre">Livre à Emprunter :</label>
                    <select id="id_livre" name="id_livre" required style="width: 100%; padding: 10px;">
                        <option value="">-- Choisir un livre (Stock) --</option>
                        <?php foreach ($livres as $livre): ?>
                            <option value="<?= $livre['id'] ?>" <?= ($livre['nombre_exemplaire'] <= 0) ? 'disabled' : '' ?>>
                                <?= htmlspecialchars($livre['titre']) ?> (<?= $livre['auteur'] ?>) [Stock: <?= $livre['nombre_exemplaire'] ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="flex: 1 1 100%; text-align: right;">
                    <button type="submit">Valider l'Emprunt</button>
                </div>
            </form>
        </section>

        <section class="admin-section">
            <h2>Emprunts en Cours (<?= count($emprunts_en_cours) ?>)</h2>
            <table class="admin-table">
                <thead><tr><th>Livre Emprunté</th><th>Lecteur</th><th>Date d'Emprunt</th><th>Date Retour Prévue</th><th>Statut</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (empty($emprunts_en_cours)): ?>
                        <tr><td colspan="6">Aucun livre n'est actuellement emprunté.</td></tr>
                    <?php else: ?>
                        <?php foreach ($emprunts_en_cours as $emprunt): ?>
                        <tr class="<?= $emprunt['en_retard'] ? 'retard-row' : '' ?>">
                            <td><?= htmlspecialchars($emprunt['titre']) ?> (<?= $emprunt['auteur'] ?>)</td>
                            <td><?= htmlspecialchars($emprunt['prenom']) ?> <?= htmlspecialchars($emprunt['nom']) ?></td>
                            <td><?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?></td>
                            
                            <td style="font-weight: bold;"><?= date('d/m/Y', strtotime($emprunt['date_retour_prevue'])) ?></td>
                            <td>
                                <?php if ($emprunt['en_retard']): ?>
                                    <span style="color: red; font-weight: bold;">🔴 EN RETARD</span>
                                <?php else: ?>
                                    <span style="color: green;">🟢 À temps</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="action-btns">
                                <form action="emprunts.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="retourner">
                                    <input type="hidden" name="id_livre_retour" value="<?= $emprunt['id_livre'] ?>">
                                    <input type="hidden" name="id_lecteur_retour" value="<?= $emprunt['id_lecteur'] ?>">
                                    <input type="hidden" name="date_emprunt_cle" value="<?= $emprunt['date_emprunt'] ?>">
                                    <button type="submit" style="background-color: #28a745;">Marquer comme Rendu</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="admin-section">
            <h2>Historique des Retours (<?= count($historique_rendu) ?>)</h2>
            <table class="admin-table">
                <thead><tr><th>Livre Rendu</th><th>Lecteur</th><th>Date d'Emprunt</th><th>Date de Retour Réelle</th></tr></thead>
                <tbody>
                    <?php if (empty($historique_rendu)): ?>
                        <tr><td colspan="4">Aucun livre n'a encore été marqué comme rendu.</td></tr>
                    <?php else: ?>
                        <?php foreach ($historique_rendu as $item): 
                            $date_prevue_retour = date('Y-m-d', strtotime($item['date_emprunt'] . ' +30 days'));
                            $date_retour_reelle = $item['date_retour'];
                            $en_retard_final = (strtotime($date_retour_reelle) > strtotime($date_prevue_retour));
                        ?>
                        <tr class="<?= $en_retard_final ? 'retard-row' : 'rendu-ok-row' ?>">
                            <td><?= htmlspecialchars($item['titre']) ?> (<?= $item['auteur'] ?>)</td>
                            <td><?= htmlspecialchars($item['prenom']) ?> <?= htmlspecialchars($item['nom']) ?></td>
                            <td><?= date('d/m/Y', strtotime($item['date_emprunt'])) ?></td>
                            <td style="font-weight: bold;">
                                <?= date('d/m/Y', strtotime($date_retour_reelle)) ?>
                                <?php if ($en_retard_final): ?>
                                    <span style="color: red; font-size: 0.9em;">(En retard)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Bibliothèque St Augustin. Le savoir est une quête.</p>
    </footer>
</body>
</html>