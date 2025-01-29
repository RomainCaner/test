<?php /*
session_start();
include 'functions.php'; // Inclut display_header(), display_footer(), etc.

// Vérification de session + rôle
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['user_role'] != 'A') { // rôle Administrateur attendu
    header("Location: index.php");
    exit;
}

// Récupération de l'ID utilisateur
$user_id = $_SESSION['user_id'];

// Connexion à la base
$conn = connexion();


$query_classes = "
    SELECT idClasse
    FROM classe
    ORDER BY idClasse ASC
";
$sth_classes = $conn->prepare($query_classes);

try {
    $sth_classes->execute();
    $listeClasses = $sth_classes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    die("Erreur lors de la requête SQL pour les classes : " . $ex->getMessage());
}


$query_annees = "
    SELECT idAnneeScolaire, libAnneeScolaire
    FROM anneescolaire
    ORDER BY idAnneeScolaire ASC
";
$sth_annees = $conn->prepare($query_annees);

try {
    $sth_annees->execute();
    $listeAnnees = $sth_annees->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    die("Erreur lors de la requête SQL pour les années scolaires : " . $ex->getMessage());
}


$query_statuts = "
    SELECT idStatut, libStatut
    FROM statut
    ORDER BY libStatut ASC
";
$sth_statuts = $conn->prepare($query_statuts);
$sth_statuts->execute();
$all_statuts = $sth_statuts->fetchAll(PDO::FETCH_ASSOC);


$classeChoisie = isset($_GET['classe']) ? $_GET['classe'] : '';
$anneeChoisie  = isset($_GET['annee'])  ? $_GET['annee']  : '';
$statutChoisi  = isset($_GET['statut']) ? $_GET['statut'] : '';


$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'nomUser';
$order_dir = (isset($_GET['order_dir']) && $_GET['order_dir'] === 'asc') ? 'asc' : 'desc';
$next_order_dir = ($order_dir === 'asc') ? 'desc' : 'asc';


function generate_sort_link($column, $label, $current_order_by, $current_order_dir, $next_order_dir, $classe, $annee, $statut)
{
    // Conserve les filtres (classe, annee, statut) dans l’URL
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


$query = "
    SELECT 
        utilisateur.idUser,
        inscription.idClasse,
        inscription.idAnneeScolaire, 
        anneescolaire.libAnneeScolaire,
        utilisateur.nomUser,
        utilisateur.prenomUser,
        COALESCE(statut.libStatut, 'Pas encore de stage') AS libStatut
    FROM inscription
    JOIN utilisateur 
        ON inscription.idEtudiant = utilisateur.idUser
    JOIN anneescolaire 
        ON inscription.idAnneeScolaire = anneescolaire.idAnneeScolaire
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

// Tableau pour stocker les conditions WHERE dynamiques
$conditions = [];
$params = [];

// Ajout des conditions en fonction des filtres sélectionnés
if (!empty($classeChoisie)) {
    $conditions[] = "inscription.idClasse = :classe";
    $params[':classe'] = $classeChoisie;
}

if (!empty($anneeChoisie)) {
    $conditions[] = "inscription.idAnneeScolaire = :annee";
    $params[':annee'] = $anneeChoisie;
}

// Filtre sur le statut s_latest.idStatut
if (!empty($statutChoisi)) {
    $conditions[] = "s_latest.idStatut = :statut";
    $params[':statut'] = $statutChoisi;
}

// Construction de la clause WHERE si nécessaire
if (count($conditions) > 0) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

// Gestion du tri
$allowed_order_columns = ['nomUser', 'prenomUser', 'libStatut', 'libAnneeScolaire', 'idClasse'];
if (in_array($order_by, $allowed_order_columns)) {
    $query .= " ORDER BY " . $order_by . " " . strtoupper($order_dir);
} else {
    // Valeur par défaut si le tri demandé n'est pas autorisé
    $query .= " ORDER BY nomUser ASC";
}

// Préparation et exécution de la requête
$sth = $conn->prepare($query);

// Liaison des paramètres dynamiques
foreach ($params as $key => $value) {
    $sth->bindValue($key, $value);
}

try {
    $sth->execute();
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    die("Erreur lors de la requête SQL : " . $ex->getMessage());
}


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
    <title>Accueil Admin - Portail des Stages</title>
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
        <p>Connecté en tant que : <?= htmlspecialchars($prenomUser . ' ' . $nomUser) ?></p>
    </section>

    <!-- MENU de Filtre -->
    <div class="fab-container" id="fabContainer">
        <div class="fab-menu show" id="fabMenu">
            <h3>Filtrer</h3>
            <form method="get" action="">
                <label for="classe">Classe</label>
                <select name="classe" id="classe">
                    <option value="">Choisir une classe</option>
                    <?php
                    foreach ($listeClasses as $classe) {
                        $idClasse = htmlspecialchars($classe['idClasse']);
                        $selected = ($idClasse == $classeChoisie) ? 'selected' : '';
                        echo "<option value=\"$idClasse\" $selected>$idClasse</option>";
                    }
                    ?>
                </select>

                <label for="annee">Année scolaire</label>
                <select name="annee" id="annee">
                    <option value="">Choisir une année</option>
                    <?php
                    foreach ($listeAnnees as $annee) {
                        $idAnnee = htmlspecialchars($annee['idAnneeScolaire']);
                        $libAnnee = htmlspecialchars($annee['libAnneeScolaire']);
                        $selected = ($idAnnee == $anneeChoisie) ? 'selected' : '';
                        echo "<option value=\"$idAnnee\" $selected>$libAnnee</option>";
                    }
                    ?>
                </select>

                <!-- NOUVEAU : Filtrer par Statut -->
                <label for="statut">Statut</label>
                <select name="statut" id="statut">
                    <option value="">Choisir un statut</option>
                    <?php
                    // Afficher la liste de tous les statuts
                    foreach ($all_statuts as $st) {
                        $idStatut = htmlspecialchars($st['idStatut']);
                        $libStatut = htmlspecialchars($st['libStatut']);
                        $selected = ($idStatut == $statutChoisi) ? 'selected' : '';
                        echo "<option value=\"$idStatut\" $selected>$libStatut</option>";
                    }
                    ?>
                </select>

                <button type="submit" class="btn-ok">OK</button>
            </form>
        </div>
    </div>

    <main>
        <section class="stage-list">
            <h2 class="titre">Recherche élève (Administration)</h2>
            <div class="table-container">
                <table class="stage-table">
                    <thead>
                        <tr>
                            <th>
                                <!-- On autorise éventuellement le tri par libAnneeScolaire -->
                                <?= generate_sort_link(
                                    'libAnneeScolaire',
                                    'Année',
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
                                    'idClasse',
                                    'Classe',
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
                                        <!-- IMPORTANT : on transmet également la classe et l'année -->
                                        <form method="post" action="detail_eleve_admin.php">
                                            <input type="hidden" name="selected_user"   value="<?= htmlspecialchars($row['idUser']) ?>">
                                            <input type="hidden" name="selected_classe" value="<?= htmlspecialchars($row['idClasse']) ?>">
                                            <input type="hidden" name="selected_annee"  value="<?= htmlspecialchars($row['idAnneeScolaire']) ?>">
                                            <button
                                                type="submit"
                                                class="link-button">
                                                Voir
                                            </button>
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
</body>
</html>
