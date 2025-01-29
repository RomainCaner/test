<?php
session_start();
include 'functions.php'; // Inclure les fonctions nécessaires

if (!isset($_SESSION['user_id'])) {
    header("Location: login_selection.php");
    exit;
}

if (!isset($_SESSION['siren']) || !isset($_SESSION['nic']) || !isset($_SESSION['tuteur_id']) || !isset($_SESSION['gerant_id'])) {
    header("Location: new_stage_step5.php");
    exit;
}

$status_id = 2; // On va mettre le statut du stage à 2 -> Stage validé lorsque le formulaire sera envoyé.

$user_id = $_SESSION['user_id'];
$siren = $_SESSION['siren'];
$nic = $_SESSION['nic'];
$tuteur_id = $_SESSION['tuteur_id'];
$gerant_id = $_SESSION['gerant_id'];
$success_message = "";
$error_message = "";

// Connexion à la base de données
$conn = connexion();

// Vérifie si on est en mode modification de stage
$idStage = isset($_SESSION['idStage']) ? (int)$_SESSION['idStage'] : null;

if ($idStage) {
    // Requête pour récupérer les informations du stage existant
    $sql_fetch_stage = "SELECT * FROM stage WHERE idStage = :idStage";
    $stmt_fetch_stage = $conn->prepare($sql_fetch_stage);
    $stmt_fetch_stage->bindParam(':idStage', $idStage, PDO::PARAM_INT);
    $stmt_fetch_stage->execute();
    $stage = $stmt_fetch_stage->fetch(PDO::FETCH_ASSOC);

    if ($stage) {
        // Assigner les données du stage aux variables correspondantes
        $dateDebutStage   = $stage['dateDebutStage'];
        $dateFinStage     = $stage['dateFinStage'];
        $dureeHebdoStage  = $stage['dureeHebdoStage'];
        $activitesStage   = $stage['activitesStage'];
        $adrEtudiantStage = $stage['adrEtudiantStage'];
        $nomLieuStage     = $stage['nomLieuStage'];
        $contactLieuStage = $stage['contactLieuStage'];
        $adrLieuStage     = $stage['adrLieuStage'];
    } else {
        $error_message = "Erreur : le stage spécifié n'a pas été trouvé.";
    }
}

// Récupérer la classe et l'année scolaire active de l'étudiant
$sql_inscription = "SELECT inscription.idClasse, inscription.idAnneeScolaire
                    FROM inscription
                    JOIN anneeScolaire ON inscription.idAnneeScolaire = anneeScolaire.idAnneeScolaire
                    WHERE inscription.idEtudiant = :idEtudiant
                      AND anneeScolaire.estActiveAnneeScolaire = 1
                    LIMIT 1";

$stmt_inscription = $conn->prepare($sql_inscription);
$stmt_inscription->bindParam(':idEtudiant', $user_id, PDO::PARAM_INT);
$stmt_inscription->execute();
$inscription = $stmt_inscription->fetch(PDO::FETCH_ASSOC);

if (!$inscription) {
    $error_message = "Erreur : l'étudiant n'est pas inscrit.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $inscription) {
    // =====================
    // Récupération des champs communs
    // =====================
    $dateDebutStage  = $_POST['dateDebutStage'];
    $dateFinStage    = $_POST['dateFinStage'];
    $dureeHebdoStage = $_POST['dureeHebdoStage'];
    $activitesStage  = $_POST['activitesStage'];

    // =====================
    // Gestion des adresses externes
    // =====================
    // Si la case "Adresse différente du domicile" est cochée, on récupère la valeur ; sinon, on vide.
    if (isset($_POST['adrEtudiantStageCheck'])) {
        $adrEtudiantStage = $_POST['adrEtudiantStage'] ?? ''; //ESSAYER AVEC NULL
    } else {
        $adrEtudiantStage = '';
    }

    // Si la case "Adresse du lieu de stage différente" est cochée, on récupère ; sinon, on vide.
    if (isset($_POST['adrLieuStageCheck'])) {
        $nomLieuStage     = $_POST['nomLieuStage'] ?? '';
        $contactLieuStage = $_POST['contactLieuStage'] ?? '';
        $adrLieuStage     = $_POST['adrLieuStage'] ?? '';
    } else {
        $nomLieuStage     = '';
        $contactLieuStage = '';
        $adrLieuStage     = '';
    }

    $idClasse        = $inscription['idClasse'];
    $idAnneeScolaire = $inscription['idAnneeScolaire'];

    $conn->beginTransaction();
    try {
        // Vérifie si on a un stage en cours de modification (UPDATE) ou si c'est un nouveau stage (INSERT)
        if (!is_null($idStage)) {
            // ==================
            //   MODE UPDATE
            // ==================
            $sql_update = "UPDATE stage
                           SET dateDebutStage    = :dateDebutStage,
                               dateFinStage      = :dateFinStage,
                               dureeHebdoStage   = :dureeHebdoStage,
                               activitesStage    = :activitesStage,
                               adrEtudiantStage  = :adrEtudiantStage,
                               nomLieuStage      = :nomLieuStage,
                               contactLieuStage  = :contactLieuStage,
                               adrLieuStage      = :adrLieuStage,
                               idMaitreDeStage   = :idMaitreDeStage,
                               idSignataire      = :idSignataire,
                               idClasse          = :idClasse,
                               idAnneeScolaire   = :idAnneeScolaire,
                               idEtudiant        = :idEtudiant,
                               SIREN             = :siren,
                               NIC               = :nic,
                               idStatut          = :idStatut
                           WHERE idStage         = :idStage";

            $stmt_update = $conn->prepare($sql_update);

            $stmt_update->bindParam(':dateDebutStage',   $dateDebutStage);
            $stmt_update->bindParam(':dateFinStage',     $dateFinStage);
            $stmt_update->bindParam(':dureeHebdoStage',  $dureeHebdoStage);
            $stmt_update->bindParam(':activitesStage',   $activitesStage);
            $stmt_update->bindParam(':adrEtudiantStage', $adrEtudiantStage);
            $stmt_update->bindParam(':nomLieuStage',     $nomLieuStage);
            $stmt_update->bindParam(':contactLieuStage', $contactLieuStage);
            $stmt_update->bindParam(':adrLieuStage',     $adrLieuStage);
            $stmt_update->bindParam(':idMaitreDeStage',  $tuteur_id);
            $stmt_update->bindParam(':idSignataire',     $gerant_id);
            $stmt_update->bindParam(':idClasse',         $idClasse);
            $stmt_update->bindParam(':idAnneeScolaire',  $idAnneeScolaire);
            $stmt_update->bindParam(':idEtudiant',       $user_id);
            $stmt_update->bindParam(':siren',            $siren);
            $stmt_update->bindParam(':nic',              $nic);
            $stmt_update->bindParam(':idStatut',         $status_id, PDO::PARAM_INT);
            $stmt_update->bindParam(':idStage',          $idStage, PDO::PARAM_INT);

            $stmt_update->execute();
            $conn->commit();

            $success_message = "Le stage a été mis à jour avec succès.";
            header("Location: new_stage_summary.php");
            exit();
        } else {
            // ==================
            //   MODE INSERT
            // ==================
            $sql_insert = "INSERT INTO stage (
                               dateDebutStage,
                               dateFinStage,
                               dureeHebdoStage,
                               activitesStage,
                               adrEtudiantStage,
                               nomLieuStage,
                               contactLieuStage,
                               adrLieuStage,
                               idMaitreDeStage,
                               idSignataire,
                               idClasse,
                               idAnneeScolaire,
                               idEtudiant,
                               SIREN,
                               NIC,
                               idStatut
                           ) VALUES (
                               :dateDebutStage,
                               :dateFinStage,
                               :dureeHebdoStage,
                               :activitesStage,
                               :adrEtudiantStage,
                               :nomLieuStage,
                               :contactLieuStage,
                               :adrLieuStage,
                               :idMaitreDeStage,
                               :idSignataire,
                               :idClasse,
                               :idAnneeScolaire,
                               :idEtudiant,
                               :siren,
                               :nic,
                               :idStatut
                           )";

            $stmt_insert = $conn->prepare($sql_insert);

            $stmt_insert->bindParam(':dateDebutStage',   $dateDebutStage);
            $stmt_insert->bindParam(':dateFinStage',     $dateFinStage);
            $stmt_insert->bindParam(':dureeHebdoStage',  $dureeHebdoStage);
            $stmt_insert->bindParam(':activitesStage',   $activitesStage);
            $stmt_insert->bindParam(':adrEtudiantStage', $adrEtudiantStage);
            $stmt_insert->bindParam(':nomLieuStage',     $nomLieuStage);
            $stmt_insert->bindParam(':contactLieuStage', $contactLieuStage);
            $stmt_insert->bindParam(':adrLieuStage',     $adrLieuStage);
            $stmt_insert->bindParam(':idMaitreDeStage',  $tuteur_id);
            $stmt_insert->bindParam(':idSignataire',     $gerant_id);
            $stmt_insert->bindParam(':idClasse',         $idClasse);
            $stmt_insert->bindParam(':idAnneeScolaire',  $idAnneeScolaire);
            $stmt_insert->bindParam(':idEtudiant',       $user_id);
            $stmt_insert->bindParam(':siren',            $siren);
            $stmt_insert->bindParam(':nic',              $nic);
            $stmt_insert->bindParam(':idStatut',         $status_id, PDO::PARAM_INT);

            $stmt_insert->execute();
            $stage_id = $conn->lastInsertId();

            // Enregistrer l'idStage dans la session
            $_SESSION['idStage'] = $stage_id;

            $conn->commit();

            $success_message = "Le stage a été ajouté avec succès.";
            header("Location: new_stage_summary.php");
            exit();
        }
    } catch (Exception $e) {
        // Rollback et message d’erreur si problème
        $conn->rollBack();
        $error_message = "Une erreur s'est produite lors de l'ajout ou de la modification du stage: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Étape 6 : Informations du Stage</title>
    <link rel="stylesheet" href="global_css.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <script>
    function toggleAdditionalInfo(sectionId) {
        var checkBox = document.getElementById(sectionId + "Check");
        var additionalInfo = document.getElementById(sectionId + "Info");
        
        // Sélectionner tous les champs input (ou textarea) contenus dans la section
        var inputs = additionalInfo.querySelectorAll("input, textarea");

        if (checkBox.checked) {
            additionalInfo.style.display = "block";
            inputs.forEach(function(input) {
                input.required = true; // Rendre les champs requis
            });
        } else {
            additionalInfo.style.display = "none";
            inputs.forEach(function(input) {
                input.required = false; // Retirer l'obligation
            });
        }
    }
</script>

</head>

<body>
    <?php display_header(); ?>

    <main>
    <div class="step-indicator">
            <div class="step"><span>1</span></div>
            <div class="step"><span>2</span></div>
            <div class="step"><span>3</span></div>
            <div class="step"><span>4</span></div>
            <div class="step"><span>5</span></div>
            <div class="step active"><span>6</span></div>
        </div>
        <section class="form-content">
            <h2>Informations du Stage</h2>
            <?php if ($error_message): ?>
                <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <p class="success"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>

            <form action="new_stage_step6.php" method="post" onsubmit="return validateForm();">
                <div class="form-group">
                    <label for="dateDebutStage">Date de Début :</label>
                    <input type="date" id="dateDebutStage" name="dateDebutStage"
                           value="<?php echo htmlspecialchars($dateDebutStage ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="dateFinStage">Date de Fin :</label>
                    <input type="date" id="dateFinStage" name="dateFinStage"
                           value="<?php echo htmlspecialchars($dateFinStage ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="dureeHebdoStage">Durée Hebdomadaire :</label>
                    <input type="number" id="dureeHebdoStage" name="dureeHebdoStage"
                           value="<?php echo htmlspecialchars($dureeHebdoStage ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="activitesStage">Activités :</label>
                    <textarea id="activitesStage" name="activitesStage" required><?php
                        echo htmlspecialchars($activitesStage ?? '');
                    ?></textarea>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="adrEtudiantStageCheck" name="adrEtudiantStageCheck"
                           onclick="toggleAdditionalInfo('adrEtudiantStage')"
                           <?php echo !empty($adrEtudiantStage) ? 'checked' : ''; ?>>
                    <label for="adrEtudiantStageCheck">
                        Adresse du domicile de l'élève durant le stage différente de l'adresse connue ?
                    </label>
                </div>
                <div id="adrEtudiantStageInfo" class="additional-info"
                     style="<?php echo !empty($adrEtudiantStage) ? 'display: block;' : 'display: none;'; ?>">
                    <div class="form-group">
                        <label for="adrEtudiantStage">Adresse de l'élève durant le stage :</label>
                        <input type="text" id="adrEtudiantStage" name="adrEtudiantStage"
                               value="<?php echo htmlspecialchars($adrEtudiantStage ?? ''); ?>">
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="adrLieuStageCheck" name="adrLieuStageCheck"
                           onclick="toggleAdditionalInfo('adrLieuStage')"
                           <?php echo (!empty($nomLieuStage) || !empty($contactLieuStage) || !empty($adrLieuStage)) ? 'checked' : ''; ?>>
                    <label for="adrLieuStageCheck">
                        Adresse du lieu de stage différente de celle de l'entreprise ?
                    </label>
                </div>
                <div id="adrLieuStageInfo" class="additional-info"
                     style="<?php echo (!empty($nomLieuStage) || !empty($contactLieuStage) || !empty($adrLieuStage)) ? 'display: block;' : 'display: none;'; ?>">
                    <div class="form-group">
                        <label for="nomLieuStage">Dénomination du lieu de stage :</label>
                        <input type="text" id="nomLieuStage" name="nomLieuStage"
                               value="<?php echo htmlspecialchars($nomLieuStage ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="contactLieuStage">Numéro de téléphone du lieu de stage :</label>
                        <input type="text" id="contactLieuStage" name="contactLieuStage"
                               value="<?php echo htmlspecialchars($contactLieuStage ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="adrLieuStage">Adresse du lieu de stage :</label>
                        <input type="text" id="adrLieuStage" name="adrLieuStage"
                               value="<?php echo htmlspecialchars($adrLieuStage ?? ''); ?>">
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="button" onclick="window.location.href='new_stage_step5.php'">Retour</button>
                    <button type="submit">Voir le résumé</button>
                </div>
            </form>
        </section>
    </main>

    <?php display_footer(); ?>
    <?php loading(); ?>
</body>


<script>
function validateForm() {
    const dateDebut = document.getElementById("dateDebutStage").value;
    const dateFin   = document.getElementById("dateFinStage").value;
    const dureeHebdo = document.getElementById("dureeHebdoStage").value;

    const debut = new Date(dateDebut);
    const fin   = new Date(dateFin);

    // Vérification : date de fin doit être supérieure à la date de début
    if (debut >= fin) {
        alert("La date de fin doit être supérieure à la date de début.");
        return false;
    }

    // Vérification : la durée hebdomadaire doit être > 0
    if (dureeHebdo <= 0) {
        alert("La durée hebdomadaire doit être supérieure à 0.");
        return false;
    }

    return true;
}
</script>

</html>
