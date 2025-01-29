<?php /*
session_start();
include 'functions.php'; // Inclure les fonctions nécessaires

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Récupération de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// 1) Si on reçoit un selected_user/classe/annee en POST, on met à jour la session
if (isset($_POST['selected_user'])) {
    $_SESSION['selected_user'] = $_POST['selected_user'];
}
if (isset($_POST['selected_classe'])) {
    $_SESSION['selected_classe'] = $_POST['selected_classe'];
}
if (isset($_POST['selected_annee'])) {
    $_SESSION['selected_annee'] = $_POST['selected_annee'];
}

// 2) On s'assure que nos variables prennent la valeur de la session si elle existe
$selected_user   = $_SESSION['selected_user']   ?? null;
$selected_classe = $_SESSION['selected_classe'] ?? null;
$selected_annee  = $_SESSION['selected_annee']  ?? null;

// (Exemple) bouton "Retour"
$submitR = isset($_POST['submitR']);

// Connexion à la base de données
$conn = connexion();

// Pour la sélection SLAM/SISR
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selection'])) {
    $_SESSION['selection'] = $_POST['selection'];
}
if (isset($_POST['modifier_selection'])) {
    unset($_SESSION['selection']);
}
$selection_defined = isset($_SESSION['selection']);

// ---------------------------------------------------------------------
// RÉCUPÉRATION INFOS DE L'ÉLÈVE SÉLECTIONNÉ (filtre : user, classe, année)
// ---------------------------------------------------------------------
$query_info = "
    SELECT 
        utilisateur.prenomUser, 
        utilisateur.nomUser, 
        inscription.idClasse,
        anneeScolaire.libAnneeScolaire
    FROM utilisateur
    JOIN etudiant 
      ON utilisateur.idUser = etudiant.idEtudiant
    JOIN inscription 
      ON etudiant.idEtudiant = inscription.idEtudiant
    JOIN classe 
      ON inscription.idClasse = classe.idClasse
    JOIN anneeScolaire 
      ON inscription.idAnneeScolaire = anneeScolaire.idAnneeScolaire
    WHERE utilisateur.idUser       = :selected_user
      AND inscription.idClasse     = :selected_classe
      AND inscription.idAnneeScolaire = :selected_annee
";
$stmt_info = $conn->prepare($query_info);
$stmt_info->bindParam(':selected_user',   $selected_user,   PDO::PARAM_INT);
$stmt_info->bindParam(':selected_classe', $selected_classe, PDO::PARAM_STR);
$stmt_info->bindParam(':selected_annee',  $selected_annee,  PDO::PARAM_INT);
$stmt_info->execute();
$user_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

// On récupère le nom/prénom de l'élève
if ($user_info) {
    $prenomUser         = $user_info['prenomUser'];
    $nomUser            = $user_info['nomUser'];
    $idClasse           = $user_info['idClasse'];
    $libAnneeScolaire   = $user_info['libAnneeScolaire'];
} else {
    // Si on n'a rien, c'est que l'élève n'existe pas ou la classe/année n'est pas la bonne
    $prenomUser         = "Utilisateur";
    $nomUser            = "Inconnu";
    $idClasse           = "";
    $libAnneeScolaire   = "";
}

// ---------------------------------------------------------------------
// Requête pour récupérer les informations sur les stages 
// (filtre : idEtudiant, classe, année)
// ---------------------------------------------------------------------
$sql_stages = "
    SELECT 
        s.idStage,
        s.nomLieuStage,
        st.libStatut,
        s.dateDebutStage,
        s.dateFinStage,
        CONCAT(s.SIREN, s.NIC) AS SIRET,
        e.denominationEtab AS nomEntreprise, 
        s.idClasse,
        a.libAnneeScolaire,
        s.idStatut,
        u.prenomUser AS prenomEnseignant,
        u.nomUser   AS nomEnseignant
    FROM stage s
    JOIN etablissement e 
      ON s.SIREN = e.SIREN 
     AND s.NIC   = e.NIC
    JOIN statut st 
      ON s.idStatut = st.idStatut
    JOIN anneeScolaire a 
      ON s.idAnneeScolaire = a.idAnneeScolaire
    LEFT JOIN enseignant en 
      ON s.idEnseignant = en.idEnseignant
    LEFT JOIN utilisateur u 
      ON en.idEnseignant = u.idUser
    WHERE s.idEtudiant      = :selected_user
      AND s.idClasse        = :selected_classe
      AND s.idAnneeScolaire = :selected_annee
    ORDER BY s.idStage DESC
";
$stmt_stages = $conn->prepare($sql_stages);
$stmt_stages->bindParam(':selected_user',   $selected_user,   PDO::PARAM_INT);
$stmt_stages->bindParam(':selected_classe', $selected_classe, PDO::PARAM_STR);
$stmt_stages->bindParam(':selected_annee',  $selected_annee,  PDO::PARAM_INT);
$stmt_stages->execute();
$result = $stmt_stages->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------------------------------------------------
// Vérification et mise à jour de l'état de stage selon la date (exemple)
// ---------------------------------------------------------------------
$current_date = date('Y-m-d');
foreach ($result as $row) {
    if ($current_date == $row['dateFinStage'] && $row['idStatut'] != 7) {
        $update_query = "UPDATE stage SET idStatut = 7 WHERE idStage = :idStage";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindParam(':idStage', $row['idStage'], PDO::PARAM_INT);
        $update_stmt->execute();
    }
}

// ---------------------------------------------------------------------
// Gestion de la soumission du formulaire (approuver, rejeter, signer, clôturer, annuler...)
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['idStage'])) {
    $idStage = $_POST['idStage'];
    $action  = $_POST['action'];

    // Récupérer le statut actuel du stage
    $status_query = "SELECT idStatut FROM stage WHERE idStage = :idStage";
    $status_stmt  = $conn->prepare($status_query);
    $status_stmt->bindParam(':idStage', $idStage, PDO::PARAM_INT);
    $status_stmt->execute();
    $current_status = $status_stmt->fetch(PDO::FETCH_ASSOC);

    if ($current_status) {
        $idStatut_update = null;

        // Cas 1 : Approuver
        if ($action == 'approuver' && $current_status['idStatut'] == 2) {
            // Fiche validée par l'élève -> on peut l'approuver
            $idEnseignant    = isset($_POST['idEnseignant']) ? $_POST['idEnseignant'] : null;
            $idStatut_update = 3; // Approuvé
            // On met aussi l'idEnseignant
            $update_query = "
                UPDATE stage
                   SET idStatut = :idStatut_update,
                       idEnseignant = :idEnseignant
                 WHERE idStage = :idStage
            ";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bindParam(':idStatut_update', $idStatut_update, PDO::PARAM_INT);
            $update_stmt->bindParam(':idEnseignant',     $idEnseignant,    PDO::PARAM_INT);
            $update_stmt->bindParam(':idStage',         $idStage,         PDO::PARAM_INT);

            try {
                $update_stmt->execute();
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } catch (PDOException $e) {
                echo "PDO Error: " . $e->getMessage();
                exit;
            }
        }
        // Cas 2 : Rejeter
        elseif ($action == 'rejeter' && $current_status['idStatut'] == 2) {
            $idStatut_update = 1; // Rejeté
        }
        // Cas 3 : Signer
        elseif ($action == 'signer' && $current_status['idStatut'] == 3) {
            $idStatut_update = 4; // Convention signée
        }
        // Cas 4 : Clôturer
        elseif ($action == 'cloturer' && $current_status['idStatut'] == 4) {
            $idStatut_update = 6; // Stage clôturé
        }
        // Cas 5 : Annuler
        elseif ($action == 'annuler') {
            // Annulation possible si le stage n'est pas déjà annulé (5) ou terminé (6)
            if (!in_array($current_status['idStatut'], [5, 6])) {
                $idStatut_update = 5; // Annulé
            }
        }

        // Mettre à jour le statut s'il y a une mise à jour (autre que l'approbation qui se fait au-dessus)
        if (!is_null($idStatut_update) && $action != 'approuver') {
            $update_query = "
                UPDATE stage
                   SET idStatut = :idStatut_update
                 WHERE idStage  = :idStage
            ";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bindParam(':idStatut_update', $idStatut_update, PDO::PARAM_INT);
            $update_stmt->bindParam(':idStage',         $idStage,         PDO::PARAM_INT);
            try {
                $update_stmt->execute();
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } catch (PDOException $e) {
                echo "PDO Error: " . $e->getMessage();
                exit;
            }
        }
    } else {
        echo "Statut du stage non trouvé.";
    }
}

// ---------------------------------------------------------------------
// script pour la selection SLAM/SISR (exemple)
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['selection']) && !empty($_POST['selection'])) {
        // Mise à jour de la spécialité dans la base de données
        $specialite = $_POST['selection'];
        // On update l'étudiant correspondant
        $update_query = "
            UPDATE etudiant
               SET optionEtudiant = :specialite
             WHERE idetudiant    = :selected_user
        ";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindParam(':specialite',    $specialite,     PDO::PARAM_STR);
        $update_stmt->bindParam(':selected_user', $selected_user,  PDO::PARAM_INT);

        try {
            $update_stmt->execute();
            $_SESSION['selection'] = $specialite;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $e) {
            echo "PDO Error: " . $e->getMessage();
            exit;
        }
    } elseif (isset($_POST['modifier_selection'])) {
        unset($_SESSION['selection']);
    }
}

$selection_defined = isset($_SESSION['selection']);

// Bouton retour
if ($submitR) {
    header('Location: index_admin.php');
    exit();
}

// ---------------------------------------------------------------------
// Re-récupérer la liste des stages après mises à jour éventuelles
// ---------------------------------------------------------------------
$stmt_stages->execute();
$result = $stmt_stages->fetchAll(PDO::FETCH_ASSOC);

// Vérifier s'il y a des stages en cours ou terminés
$can_create_new_stage = true;
foreach ($result as $row) {
    // Exemple de logique : si un stage n'est ni terminé ni annulé, on bloque la création
    if ($row['libStatut'] != 'Stage terminé' && $row['libStatut'] != 'Stage annulé') {
        $can_create_new_stage = false;
        break;
    }
}

// Exécuter la première requête (exemple de stats)
$sql1 = "SELECT COUNT(*) AS count1 FROM stage WHERE idStatut >= 2";
$stmt1 = $conn->prepare($sql1);
$stmt1->execute();
$row1 = $stmt1->fetch(PDO::FETCH_ASSOC);
$_SESSION['convention_eleve'] = $row1 ? $row1['count1'] : 0;

// Exécuter la deuxième requête (exemple de stats)
$sql2 = "SELECT COUNT(*) AS count2 FROM stage";
$stmt2 = $conn->prepare($sql2);
$stmt2->execute();
$row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
$_SESSION['stage_total'] = $row2 ? $row2['count2'] : 0;

// ---------------------------------------------------------------------
// Récupérer la liste des enseignants (pour la pop-up) 
// filtrés sur la même classe + même année
// ---------------------------------------------------------------------
$sql_enseignants = "
    SELECT e.idEnseignant, u.nomUser, u.prenomUser
      FROM enseignant e
      JOIN utilisateur u 
        ON e.idEnseignant = u.idUser
      JOIN enseigner en
        ON e.idEnseignant = en.idEnseignant
     WHERE en.idClasse = :selected_classe
       AND en.idAnneeScolaire = :selected_annee
";
$stmt_enseignants = $conn->prepare($sql_enseignants);
$stmt_enseignants->bindParam(':selected_classe', $selected_classe, PDO::PARAM_STR);
$stmt_enseignants->bindParam(':selected_annee',  $selected_annee,  PDO::PARAM_INT);
$stmt_enseignants->execute();
$enseignants = $stmt_enseignants->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Élève - Portail des Stages - Enseignant</title>
    <link rel="stylesheet" href="global_css.css">
    <!-- Import de la police Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap"
        rel="stylesheet">

    <script>
        // Script JS pour confirmation lors de la clôture
        function confirmCloture(idStage) {
            if (confirm("Voulez-vous vraiment clôturer le stage ?")) {
                var form = document.createElement("form");
                form.method = "post";
                form.action = "";

                var inputIdStage = document.createElement("input");
                inputIdStage.type = "hidden";
                inputIdStage.name = "idStage";
                inputIdStage.value = idStage;

                var inputAction = document.createElement("input");
                inputAction.type = "hidden";
                inputAction.name = "action";
                inputAction.value = "cloturer";

                form.appendChild(inputIdStage);
                form.appendChild(inputAction);

                document.body.appendChild(form);
                form.submit();
            }
        }

        // Soumettre un form pour “download” (exemple : convention)
        function submitDownloadForm(idStage) {
            var form = document.createElement("form");
            form.method = "post";
            form.action = "";

            var inputIdStage = document.createElement("input");
            inputIdStage.type = "hidden";
            inputIdStage.name = "idStage";
            inputIdStage.value = idStage;

            var inputAction = document.createElement("input");
            inputAction.type = "hidden";
            inputAction.name = "action";
            inputAction.value = "download";

            form.appendChild(inputIdStage);
            form.appendChild(inputAction);

            document.body.appendChild(form);
            form.submit();
        }

        // Confirmation lors de l'annulation
        function confirmAnnulation(idStage) {
            if (confirm("Êtes-vous sûr de vouloir annuler ce stage ?")) {
                var form = document.createElement("form");
                form.method = "post";
                form.action = "";

                var inputIdStage = document.createElement("input");
                inputIdStage.type = "hidden";
                inputIdStage.name = "idStage";
                inputIdStage.value = idStage;

                var inputAction = document.createElement("input");
                inputAction.type = "hidden";
                inputAction.name = "action";
                inputAction.value = "annuler";

                form.appendChild(inputIdStage);
                form.appendChild(inputAction);

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</head>

<body>
    <?php display_header(); ?>

    <!-- Bloc d'infos utilisateur -->
    <section class="user-info">
        <p>Élève :
            <b><?php
                if ($user_info) {
                    echo htmlspecialchars($prenomUser) . ' ' . htmlspecialchars($nomUser);
                } else {
                    echo "Aucun utilisateur sélectionné ou filtre invalide.";
                }
                ?></b>
        </p>
        <?php if ($user_info): ?>
            <p>Classe : <b><?php echo htmlspecialchars($idClasse); ?></b></p>
            <p>Année scolaire : <b><?php echo htmlspecialchars($libAnneeScolaire); ?></b></p>
        <?php endif; ?>
    </section>


    <main>
        <section class="stage-list">
            <h2 class="titre">Historique des Stages / Suivi des Démarches</h2>
            <div class="table-container">
                <?php if (empty($result)): ?>
                    <p class="empty">Aucun stage trouvé.</p>
                <?php else: ?>
                    <table class="stage-table">
                        <thead>
                            <tr>
                                <th>STATUT</th>
                                <th>Date début</th>
                                <th>Date fin</th>
                                <th>Nom Entreprise</th>
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
                if (!empty($row['dateDebutStage'])) {
                    $dateDebut = DateTime::createFromFormat('Y-m-d', $row['dateDebutStage']);
                    echo $dateDebut ? $dateDebut->format('d/m/Y') : htmlspecialchars($row['dateDebutStage']);
                } else {
                    echo "N/A";
                }
                ?>
            </td>
            <td>
                <?php
                if (!empty($row['dateFinStage'])) {
                    $dateFin = DateTime::createFromFormat('Y-m-d', $row['dateFinStage']);
                    echo $dateFin ? $dateFin->format('d/m/Y') : htmlspecialchars($row['dateFinStage']);
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
                if (!empty($row['prenomEnseignant']) && !empty($row['nomEnseignant'])) {
                    echo htmlspecialchars($row['prenomEnseignant'] . ' ' . $row['nomEnseignant']);
                } else {
                    echo "Non assigné";
                }
                ?>
            </td>
            <td>
                <!-- Documents -->
                <a href="generate_fr.php?idStage=<?php echo htmlspecialchars($row['idStage']); ?>"
                    class="download-link"
                    target="_blank">
                    Fiche de renseignement
                </a>
                <?php if ($row['idStatut'] >= 3 && $row['idStatut'] != 5): ?>
                    <br>
                    <a href="generate_cs.php"
                        onclick="submitDownloadForm(<?php echo htmlspecialchars($row['idStage']); ?>)"
                        class="download-link"
                        target="_blank">
                        Convention
                    </a>
                <?php endif; ?>
                <?php if ($row['idStatut'] >= 4 && $row['idStatut'] != 5): ?>
                    <br>
                    <a href="generate_as.php"
                        onclick="submitDownloadForm(<?php echo htmlspecialchars($row['idStage']); ?>)"
                        class="download-link"
                        target="_blank">
                        Attestation
                    </a>
                <?php endif; ?>
            </td>
            <td>
                <!-- Actions -->
                <?php if ($row['idStatut'] == 2): ?>
                    <!-- Approuver / Rejeter -->
                    <button type="button" class="btn-approuve"
                        onclick="openProfPopup(<?php echo htmlspecialchars($row['idStage']); ?>)">
                        Approuver le stage
                    </button>
                    <br>
                    <form method="post" action="" style="display:inline;">
                        <input type="hidden" name="idStage" value="<?php echo htmlspecialchars($row['idStage']); ?>">
                        <input type="hidden" name="action" value="rejeter">
                        <button type="submit" class="btn-rejete">Corriger le stage</button>
                    </form>
                    <br>
                <?php endif; ?>

                <?php if ($row['idStatut'] == 3): ?>
                    <!-- Signer -->
                    <form method="post" action="" style="display:inline;">
                        <input type="hidden" name="idStage" value="<?php echo htmlspecialchars($row['idStage']); ?>">
                        <input type="hidden" name="action" value="signer">
                        <button type="submit" class="btn-signer">Signer la convention</button>
                    </form>
                    <br>
                <?php endif; ?>

                <?php if ($row['idStatut'] == 4): ?>
                    <!-- Clôturer -->
                    <button class="btn-cloturer"
                        onclick="confirmCloture(<?php echo htmlspecialchars($row['idStage']); ?>)">
                        Clôturer le stage
                    </button>
                    <br>
                <?php endif; ?>

                <?php 
                // Bouton Annuler (si statut != 5 et != 6)
                if (!in_array($row['idStatut'], [5, 6])): 
                ?>
                    <button class="btn-rejete" 
                        onclick="confirmAnnulation(<?php echo htmlspecialchars($row['idStage']); ?>)">
                        Annuler le stage
                    </button>
                    <br>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>


                    </table>
                <?php endif; ?>
            </div>
            <br>
            <form method="post" action="index_admin.php">
                <button type="submit" name="submitR" class="btn-signer">Retour</button>
            </form>
        </section>
    </main>

    <!-- Superposition (Overlay) -->
    <div id="modal-overlay" class="modal-overlay"></div>

    <!-- Pop-up de sélection du professeur (Modal) -->
    <div id="popup-selection-prof" class="popup-modal">
        <h3>
            Sélectionner l’enseignant référent de
            <?php
            if (!empty($prenomUser) && !empty($nomUser)) {
                echo htmlspecialchars($prenomUser . ' ' . $nomUser);
            } else {
                echo "l'étudiant(e)";
            }
            ?>
        </h3>
        <form method="post" id="form-choix-prof">
            <input type="hidden" name="idStage" id="idStageHidden">
            <input type="hidden" name="action" value="approuver">

            <label for="profSelect">Enseignant :</label>
            <select name="idEnseignant" id="profSelect" required>
                <option value="">--- Sélectionnez un enseignant ---</option>
                <?php foreach ($enseignants as $ens): ?>
                    <option value="<?php echo htmlspecialchars($ens['idEnseignant']); ?>">
                        <?php echo htmlspecialchars($ens['prenomUser'] . " " . $ens['nomUser']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <br><br>
            <button type="submit">Valider</button>
            <button type="button" onclick="closeProfPopup()">Annuler</button>
        </form>
    </div>

    <script>
        // Ouvre la pop-up et affiche l'overlay
        function openProfPopup(idStage) {
            document.getElementById("idStageHidden").value = idStage;
            document.getElementById("modal-overlay").style.display = "block";
            document.getElementById("popup-selection-prof").style.display = "block";
        }

        // Ferme la pop-up et masque l'overlay
        function closeProfPopup() {
            document.getElementById("modal-overlay").style.display = "none";
            document.getElementById("popup-selection-prof").style.display = "none";
        }

        // Fermer la pop-up en cliquant sur l'overlay
        document.getElementById("modal-overlay").addEventListener("click", closeProfPopup);
    </script>

    <?php display_footer(); ?>
</body>
</html>
