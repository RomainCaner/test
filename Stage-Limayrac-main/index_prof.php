<?php
session_start();
include 'functions.php'; // Contient display_header(), display_footer(), etc.

// Vérification de session + rôle
// => on autorise Prof (P) et Admin (A).
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['user_role'] !== 'P' && $_SESSION['user_role'] !== 'A') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Connexion à la base
$conn = connexion();

// ---------------------------------------------------------------------
// 1) RÉCUPÉRATION DES LISTES (CLASSES, ANNÉES) SANS DOUBLONS
// ---------------------------------------------------------------------
if ($user_role === 'A') {
    // -- ADMIN => TOUTES LES CLASSES --
    $sql_classes = "SELECT DISTINCT idClasse
                    FROM classe
                    ORDER BY idClasse ASC";
    $sth_classes = $conn->prepare($sql_classes);
    $sth_classes->execute();
    $listeClasses = $sth_classes->fetchAll(PDO::FETCH_ASSOC);

    // -- ADMIN => TOUTES LES ANNÉES SCOLAIRES --
    $sql_annees = "SELECT DISTINCT idAnneeScolaire, libAnneeScolaire
                   FROM anneeScolaire
                   ORDER BY idAnneeScolaire ASC";
    $sth_annees = $conn->prepare($sql_annees);
    $sth_annees->execute();
    $listeAnnees = $sth_annees->fetchAll(PDO::FETCH_ASSOC);
} else {
    // -- PROF => UNIQUEMENT LES CLASSES / ANNÉES QU'IL ENSEIGNE --
    // Récup. des classes
    $sql_prof_classes = "
        SELECT DISTINCT c.idClasse
        FROM enseigner e
        JOIN classe c 
            ON e.idClasse = c.idClasse
        WHERE e.idEnseignant = :user_id
        ORDER BY c.idClasse
    ";
    $sth_prof_classes = $conn->prepare($sql_prof_classes);
    $sth_prof_classes->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $sth_prof_classes->execute();
    $listeClasses = $sth_prof_classes->fetchAll(PDO::FETCH_ASSOC);

    // Récup. des années
    $sql_prof_annees = "
        SELECT DISTINCT a.idAnneeScolaire, a.libAnneeScolaire
        FROM enseigner e
        JOIN anneeScolaire a
            ON e.idAnneeScolaire = a.idAnneeScolaire
        WHERE e.idEnseignant = :user_id
        ORDER BY a.idAnneeScolaire
    ";
    $sth_prof_annees = $conn->prepare($sql_prof_annees);
    $sth_prof_annees->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $sth_prof_annees->execute();
    $listeAnnees = $sth_prof_annees->fetchAll(PDO::FETCH_ASSOC);
}

// ---------------------------------------------------------------------
// 2) RÉCUPÉRATION DE TOUS LES STATUTS POSSIBLES (toujours identique)
// ---------------------------------------------------------------------
$query_statuts = "
    SELECT idStatut, libStatut
    FROM statut
    ORDER BY libStatut ASC
";
$sth_statuts = $conn->prepare($query_statuts);
$sth_statuts->execute();
$all_statuts = $sth_statuts->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------------------------------------------------
// 3) GESTION DES FILTRES (SESSION)
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['classe'])) {
        $_SESSION['classe'] = $_GET['classe'];
    }
    if (isset($_GET['annee'])) {
        $_SESSION['annee'] = $_GET['annee'];
    }
    if (isset($_GET['statut'])) {
        $_SESSION['statut'] = $_GET['statut'];
    }
}

// Récupération des filtres depuis la session ou valeurs par défaut
$classeChoisie = isset($_SESSION['classe']) ? $_SESSION['classe'] : '';
$anneeChoisie  = isset($_SESSION['annee'])  ? $_SESSION['annee']  : '';
$statutChoisi  = isset($_SESSION['statut']) ? $_SESSION['statut'] : '';

// ---------------------------------------------------------------------
// 4) GESTION DU TRI (optionnel)
// ---------------------------------------------------------------------
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'nomUser';
$order_dir = (isset($_GET['order_dir']) && $_GET['order_dir'] === 'asc') ? 'asc' : 'desc';
$next_order_dir = ($order_dir === 'asc') ? 'desc' : 'asc';

// Fonction pratique pour générer un lien de tri
function generate_sort_link($column, $label, $current_order_by, $current_order_dir, $next_order_dir, $classe, $annee, $statut)
{
    $icon = '';
    if ($current_order_by === $column) {
        $icon = ($current_order_dir === 'asc') ? '↑' : '↓';
    }
    return sprintf(
        '<a class="download-link" href="?order_by=%s&order_dir=%s&classe=%s&annee=%s&statut=%s" class="no-link-style">%s %s</a>',
        urlencode($column),
        urlencode($next_order_dir),
        urlencode($classe),
        urlencode($annee),
        urlencode($statut),
        htmlspecialchars($label),
        $icon
    );
}

// ---------------------------------------------------------------------
// 5) REQUÊTE PRINCIPALE POUR LA LISTE D'ÉLÈVES
// ---------------------------------------------------------------------
$query = "
    SELECT 
        utilisateur.idUser,
        inscription.idClasse,
        inscription.idAnneeScolaire, 
        anneeScolaire.libAnneeScolaire,
        utilisateur.nomUser,
        utilisateur.prenomUser,
        COALESCE(statut.libStatut, 'Pas encore de stage') AS libStatut
    FROM inscription
    JOIN utilisateur 
        ON inscription.idEtudiant = utilisateur.idUser
    JOIN anneeScolaire 
        ON inscription.idAnneeScolaire = anneeScolaire.idAnneeScolaire
    LEFT JOIN stage s_latest 
        ON s_latest.idStage = (
            SELECT stage.idStage 
            FROM stage 
            WHERE stage.idEtudiant = inscription.idEtudiant
              AND stage.idAnneeScolaire = inscription.idAnneeScolaire
            ORDER BY stage.idStage DESC 
            LIMIT 1
        )
    LEFT JOIN statut 
        ON s_latest.idStatut = statut.idStatut
";

$conditions = [];
$params = [];

// Si on est PROF : on limite aux classes de l'enseignant
if ($user_role === 'P') {
    $conditions[] = "inscription.idClasse IN (
        SELECT idClasse 
        FROM enseigner 
        WHERE idEnseignant = :user_id
    )";
    $params[':user_id'] = $user_id;
}

// Ajout des conditions selon les filtres sélectionnés
if (!empty($classeChoisie)) {
    $conditions[] = "inscription.idClasse = :classe";
    $params[':classe'] = $classeChoisie;
}

if (!empty($anneeChoisie)) {
    $conditions[] = "inscription.idAnneeScolaire = :annee";
    $params[':annee'] = $anneeChoisie;
}

if (!empty($statutChoisi)) {
    $conditions[] = "s_latest.idStatut = :statut";
    $params[':statut'] = $statutChoisi;
}

// Construction de la clause WHERE si nécessaire
if (count($conditions) > 0) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

// Tri
$allowed_order_by = ['nomUser', 'prenomUser', 'libStatut'];
if (in_array($order_by, $allowed_order_by)) {
    $query .= " ORDER BY " . $order_by . " " . strtoupper($order_dir);
}

// Préparation et exécution
$sth = $conn->prepare($query);
foreach ($params as $key => $value) {
    $sth->bindValue($key, $value);
}

try {
    $sth->execute();
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    die("Erreur lors de la requête SQL : " . $ex->getMessage());
}

// ---------------------------------------------------------------------
// 6) Récupération du nom de l'utilisateur (facultatif pour affichage)
// ---------------------------------------------------------------------
$query_user = "
    SELECT prenomUser, nomUser 
    FROM utilisateur 
    WHERE idUser = :user_id
";
$stmt_user = $conn->prepare($query_user);
$stmt_user->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_user->execute();
$user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
$prenomUser = $user_data['prenomUser'] ?? 'Utilisateur';
$nomUser    = $user_data['nomUser']    ?? 'inconnu';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Accueil - Portail des Stages</title>
    <link rel="stylesheet" href="global_css.css">
    <!-- Import de la police Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap"
        rel="stylesheet">
</head>

<body>
    <?php display_header(); ?>

    <!-- Bloc d'infos utilisateur -->
    <section class="user-info">
        <p>Connecté en tant que : <b><?= htmlspecialchars($prenomUser . ' ' . $nomUser) ?></b></p>
    </section>

    <!-- MENU de Filtre -->
    <div class="fab-container" id="fabContainer">
        <div class="fab-menu show" id="fabMenu">
            <h3>Filtrer</h3>
            <form method="get" action="">
                <!-- Année scolaire -->
                <label for="annee">Année scolaire</label>
                <select name="annee" id="annee">
                    <option value="">-- Choisir une année --</option>
                    <?php
                    foreach ($listeAnnees as $an) {
                        $anVal = $an['idAnneeScolaire'];
                        $anLib = $an['libAnneeScolaire'];
                        $selected = ($anVal == $anneeChoisie) ? 'selected' : '';
                        echo "<option value=\"" . htmlspecialchars($anVal) . "\" $selected>"
                            . htmlspecialchars($anLib)
                            . "</option>";
                    }
                    ?>
                </select>

                <!-- Classe -->
                <label for="classe">Classe</label>
                <select name="classe" id="classe">
                    <option value="">-- Choisir une classe --</option>
                    <?php
                    foreach ($listeClasses as $cl) {
                        $clVal = $cl['idClasse'];
                        $selected = ($clVal == $classeChoisie) ? 'selected' : '';
                        echo "<option value=\"" . htmlspecialchars($clVal) . "\" $selected>"
                            . htmlspecialchars($clVal)
                            . "</option>";
                    }
                    ?>
                </select>

                <!-- Statut -->
                <label for="statut">Statut</label>
                <select name="statut" id="statut">
                    <option value="">-- Choisir un statut --</option>
                    <?php
                    foreach ($all_statuts as $st) {
                        $stVal = $st['idStatut'];
                        $stLib = $st['libStatut'];
                        $selected = ($stVal == $statutChoisi) ? 'selected' : '';
                        echo "<option value=\"" . htmlspecialchars($stVal) . "\" $selected>"
                            . htmlspecialchars($stLib)
                            . "</option>";
                    }
                    ?>
                </select>

                <button type="submit" class="btn-ok">OK</button>
            </form>
        </div>
    </div>

    <main>

        <section class="stage-list">
        <h2 class="titre">Accueil - Espace Enseignant</h2>
            <div class="search-bar-container">
                <input
                    type="text"
                    class="search-bar-input"
                    id="searchInput"
                    placeholder="Rechercher un élève (nom ou prénom)..." />
            </div>


            <div class="table-container">

                <table class="stage-table">
                    <thead>
                        <tr>
                            <th>Année</th>
                            <th>Classe</th>
                            <th>
                                <?= generate_sort_link(
                                    'nomUser',
                                    'Nom',
                                    $order_by,
                                    $order_dir,
                                    $next_order_dir,
                                    $classeChoisie,
                                    $anneeChoisie,
                                    $statutChoisi
                                ) ?>
                            </th>
                            <th>
                                <?= generate_sort_link(
                                    'prenomUser',
                                    'Prénom',
                                    $order_by,
                                    $order_dir,
                                    $next_order_dir,
                                    $classeChoisie,
                                    $anneeChoisie,
                                    $statutChoisi
                                ) ?>
                            </th>
                            <th>
                                <?= generate_sort_link(
                                    'libStatut',
                                    'Statut',
                                    $order_by,
                                    $order_dir,
                                    $next_order_dir,
                                    $classeChoisie,
                                    $anneeChoisie,
                                    $statutChoisi
                                ) ?>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($result)): ?>
                            <?php foreach ($result as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['libAnneeScolaire']) ?></td>
                                    <td><?= htmlspecialchars($row['idClasse']) ?></td>
                                    <td><?= htmlspecialchars($row['nomUser']) ?></td>
                                    <td><?= htmlspecialchars($row['prenomUser']) ?></td>
                                    <td><?= htmlspecialchars($row['libStatut']) ?></td>
                                    <td>
                                        <!-- IMPORTANT: on transmet la classe et l'année -->
                                        <form method="post" action="detail_eleve_prof.php">
                                            <input type="hidden" name="selected_user"
                                                value="<?= htmlspecialchars($row['idUser']) ?>">
                                            <input type="hidden" name="selected_classe"
                                                value="<?= htmlspecialchars($row['idClasse']) ?>">
                                            <input type="hidden" name="selected_annee"
                                                value="<?= htmlspecialchars($row['idAnneeScolaire']) ?>">
                                            <button type="submit" class="link-button">Voir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty">Aucun résultat trouvé</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <?php display_footer(); ?>
    <?php loading(); ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Récupération de l'input et du tableau
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.querySelector('.stage-table tbody');

        searchInput.addEventListener('input', function() {
            const filter = searchInput.value.trim().toLowerCase();
            const searchTerms = filter.split(/\s+/); // Sépare les termes par espaces
            const rows = tableBody.getElementsByTagName('tr');

            // Pour chaque ligne, on vérifie si les termes de recherche correspondent au nom et/ou au prénom
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                if (cells.length < 4) { // Vérifie que les cellules nom et prénom existent
                    rows[i].style.display = 'none';
                    continue;
                }
                const nom = cells[2].textContent.toLowerCase();
                const prenom = cells[3].textContent.toLowerCase();

                let match = true;

                // Vérifie chaque terme de recherche
                for (let term of searchTerms) {
                    // Le terme doit correspondre soit au nom, soit au prénom
                    if (!nom.includes(term) && !prenom.includes(term)) {
                        match = false;
                        break;
                    }
                }

                if (match) {
                    rows[i].style.display = ''; // Affiche la ligne
                } else {
                    rows[i].style.display = 'none'; // Masque la ligne
                }
            }
        });
    });
</script>


</body>

</html>