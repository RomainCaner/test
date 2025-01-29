<?php
session_start();
include 'functions.php'; // Inclure les fonctions nécessaires

// Vérification de session
if (!isset($_SESSION['user_id'])) {
    header("Location: login_selection.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$conn = connexion();

// Récupération des infos de l'utilisateur de l'année active
$query = "SELECT utilisateur.prenomUser, utilisateur.nomUser, classe.idClasse, anneeScolaire.libAnneeScolaire
          FROM utilisateur 
          JOIN etudiant ON utilisateur.idUser = etudiant.idEtudiant
          JOIN inscription ON etudiant.idEtudiant = inscription.idEtudiant 
          JOIN classe ON inscription.idClasse = classe.idClasse
          JOIN anneeScolaire ON inscription.idAnneeScolaire = anneeScolaire.idAnneeScolaire
          WHERE utilisateur.idUser = :user_id
          AND anneeScolaire.estActiveAnneeScolaire = 1";

$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user_info) {
    $prenomUser        = $user_info['prenomUser'];
    $nomUser           = $user_info['nomUser'];
    $idClasse          = $user_info['idClasse'];
    $libAnneeScolaire  = $user_info['libAnneeScolaire'];
} else {
    // Valeurs par défaut si non trouvé
    $prenomUser        = "Utilisateur";
    $nomUser           = "inconnu";
    $idClasse          = "";
    $libAnneeScolaire  = "";
}

// Récupération des stages
$sql_stages = "SELECT 
                s.idStage,
                s.nomLieuStage,
                st.libStatut,
                st.idStatut,
                s.dateDebutStage,
                s.dateFinStage,
                CONCAT(s.SIREN, s.NIC) AS SIRET,
                e.denominationEtab AS nomEntreprise,  
                s.idClasse,
                a.libAnneeScolaire,
                s.idEnseignant,
                u.prenomUser AS enseignantPrenom,
                u.nomUser AS enseignantNom
               FROM stage s
               JOIN etablissement e ON s.SIREN = e.SIREN AND s.NIC = e.NIC
               JOIN statut st ON s.idStatut = st.idStatut
               JOIN anneeScolaire a ON s.idAnneeScolaire = a.idAnneeScolaire
               LEFT JOIN utilisateur u ON s.idEnseignant = u.idUser
               WHERE s.idEtudiant = :user_id
               ORDER BY s.idStage ASC";

$stmt_stages = $conn->prepare($sql_stages);
$stmt_stages->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_stages->execute();
$result = $stmt_stages->fetchAll(PDO::FETCH_ASSOC);

// Vérifier si un nouveau stage peut être créé
$can_create_new_stage = true;
foreach ($result as $row) {
    // Si on trouve un stage dont l'idStatut n'est pas 5 (annulé) ou 6 (terminé),
    // on empêche la création d'un nouveau stage.
    if ($row['idStatut'] != 6 && $row['idStatut'] != 5) {
        $can_create_new_stage = false;
        break;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Portail des Stages</title>
    <!-- Votre fichier CSS global, qui inclura aussi la partie CSS du loader -->
    <link rel="stylesheet" href="global_css.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>

    <?php
        // Affichage de l'entête et des infos étudiant (functions.php)
        display_header();
        display_info_etudiant($idClasse, $libAnneeScolaire);
    ?>

    <main>
        <section class="stage-list">
        <h2 class="titre">Accueil - Espace Étudiant</h2>
            <div class="table-container">
                <?php if (empty($result)): ?>
                    <p class="empty">Aucun stage trouvé.</p>
                <?php else: ?>
                    <table class="stage-table">
                        <thead>
                            <tr>
                                <th>STATUT</th>
                                <th>DATE DÉBUT</th>
                                <th>DATE FIN</th>
                                <th>ENTREPRISE</th>
                                <th>CLASSE</th>
                                <th>ANNÉE</th>
                                <th>ENSEIGNANT</th>
                                <th>DOCUMENTS</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['libStatut']); ?></td>
                                    <td>
                                        <?php
                                        // Formatage de la date de début
                                        if (!empty($row['dateDebutStage'])) {
                                            $dateDebut = DateTime::createFromFormat('Y-m-d', $row['dateDebutStage']);
                                            if ($dateDebut) {
                                                echo $dateDebut->format('d/m/Y');
                                            } else {
                                                echo htmlspecialchars($row['dateDebutStage']);
                                            }
                                        } else {
                                            echo "N/A";
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Formatage de la date de fin
                                        if (!empty($row['dateFinStage'])) {
                                            $dateFin = DateTime::createFromFormat('Y-m-d', $row['dateFinStage']);
                                            if ($dateFin) {
                                                echo $dateFin->format('d/m/Y');
                                            } else {
                                                echo htmlspecialchars($row['dateFinStage']);
                                            }
                                        } else {
                                            echo "N/A";
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['nomEntreprise']); ?></td>
                                    <td><?php echo htmlspecialchars($row['idClasse']); ?></td>
                                    <td><?php echo htmlspecialchars($row['libAnneeScolaire']); ?></td>
                                    <td>
                                        <?php
                                            if ($row['enseignantPrenom'] && $row['enseignantNom']) {
                                                echo htmlspecialchars($row['enseignantPrenom']) . ' ' . htmlspecialchars($row['enseignantNom']);
                                            } else {
                                                echo 'Non assigné';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Affiche certains documents en fonction du statut
                                        // Fiche de renseignements
                                        if ($row['idStatut'] >= 2 && $row['idStatut'] != 5) {
                                            echo '<a href="generate_fr.php?idStage=' . htmlspecialchars($row['idStage']) . '" class="download-link">Fiche de renseignements</a><br>';
                                        }
                                        // Convention de stage
                                        if ($row['idStatut'] >= 3 && $row['idStatut'] != 5) {
                                            echo '<a href="generate_cs.php?idStage=' . htmlspecialchars($row['idStage']) . '" class="download-link">Convention de stage</a><br>';
                                        }
                                        // Attestation de stage
                                        if ($row['idStatut'] >= 4 && $row['idStatut'] != 5) {
                                            echo '<a href="generate_as.php?idStage=' . htmlspecialchars($row['idStage']) . '" class="download-link">Attestation de stage</a><br>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Continuer la saisie si la fiche est en cours (statut = 1)
                                        if ($row['idStatut'] == 1) {
                                            echo '<a href="set_stage_session.php?idStage=' . htmlspecialchars($row['idStage']) . '" class="download-link">Continuer la saisie</a><br>';
                                        }
                                        // Annuler si la fiche est validée mais pas encore approuvée (statut = 2)
                                        if ($row['idStatut'] == 2) {
                                            echo '<a href="cancel_stage.php?idStage=' . htmlspecialchars($row['idStage']) . '" class="cancel-link">Annuler</a>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Bouton pour un nouveau stage si autorisé -->
            <?php if ($can_create_new_stage): ?>
                <a href="set_stage_session.php" class="new-stage-button">Nouveau Stage</a>
            <?php else: ?>
                <p>Vous avez déjà un stage en cours ou validé. Vous ne pouvez pas créer un nouveau stage.</p>
            <?php endif; ?>
        </section>
    </main>

    <?php display_footer(); ?>
    <?php loading();?>
</body>
</html>
