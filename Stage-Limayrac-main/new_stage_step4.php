<?php
session_start();
include 'functions.php'; // Inclure les fonctions nécessaires

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login_selection.php");
    exit;
}

// Vérifie si les informations de l'entreprise sont disponibles dans la session
if (!isset($_SESSION['siren']) || !isset($_SESSION['nic'])) {
    header("Location: new_stage_step2.php");
    exit;
}

$siren = $_SESSION['siren'];
$nic = $_SESSION['nic'];
$success_message = "";
$error_message = "";
$currentGerantId = null;

// Connexion à la base de données
$conn = connexion();

// Requête pour récupérer tous les contacts associés à l'entreprise
$sql_contacts = "SELECT * FROM contact WHERE SIREN = :siren AND NIC = :nic AND estActifContact = '1'";
$stmt_contacts = $conn->prepare($sql_contacts);
$stmt_contacts->bindParam(':siren', $siren, PDO::PARAM_STR);
$stmt_contacts->bindParam(':nic', $nic, PDO::PARAM_STR);
$stmt_contacts->execute();
$contacts = $stmt_contacts->fetchAll(PDO::FETCH_ASSOC);

// Vérifie si on est en mode modification de stage
$idStage = isset($_SESSION['idStage']) ? (int)$_SESSION['idStage'] : null;

if ($idStage) {
    // Récupérer le idSignataire actuel
    $sql_get_gerant = "SELECT idSignataire FROM stage WHERE idStage = :idStage";

    $stmt_get_gerant = $conn->prepare($sql_get_gerant);
    $stmt_get_gerant->bindParam(':idStage', $idStage, PDO::PARAM_INT);
    $stmt_get_gerant->execute();
    $result_gerant = $stmt_get_gerant->fetch(PDO::FETCH_ASSOC);
    if ($result_gerant) {
        $currentGerantId = $result_gerant['idSignataire'];
    }
}

// Vérifie si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['gerant_id'])) {
        $gerant_id = (int)$_POST['gerant_id'];

        // Commence une transaction
        $conn->beginTransaction();

        try {
            // Met à jour l'idSignataire dans la table stage
            if ($idStage) {
                $sql_update_stage = "UPDATE stage SET idSignataire = :gerant_id WHERE idStage = :idStage";
                
                $stmt_update_stage = $conn->prepare($sql_update_stage);
                $stmt_update_stage->bindParam(':gerant_id', $gerant_id, PDO::PARAM_INT);
                $stmt_update_stage->bindParam(':idStage', $idStage, PDO::PARAM_INT);
                $stmt_update_stage->execute();
            } else {
                // Si en création de stage, insérer le nouvel enregistrement avec idSignataire
        
                /*
                $sql_insert_stage = "INSERT INTO stage (idSignataire, SIREN, NIC, idEnseignant, idStatut, idMaitreDeStage, idClasse, idAnneeScolaire, idEtudiant, ...) 
                                     VALUES (:gerant_id, :siren, :nic, :idEnseignant, :idStatut, :idMaitreDeStage, :idClasse, :idAnneeScolaire, :idEtudiant, ...)";
                $stmt_insert_stage = $conn->prepare($sql_insert_stage);
                $stmt_insert_stage->bindParam(':gerant_id', $gerant_id, PDO::PARAM_INT);
                $stmt_insert_stage->bindParam(':siren', $siren, PDO::PARAM_STR);
                $stmt_insert_stage->bindParam(':nic', $nic, PDO::PARAM_STR);
                // Bind other parameters as needed
                $stmt_insert_stage->execute();
                */
            }

            // Commit la transaction
            $conn->commit();

            $_SESSION['gerant_id'] = $gerant_id;
            header("Location: new_stage_step5.php");
            exit();
        } catch (Exception $e) {
            // Rollback la transaction en cas d'erreur
            $conn->rollBack();
            $error_message = "Erreur lors de la mise à jour du gérant : " . $e->getMessage();
        }
    } 
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Étape 4 : Sélectionner le signataire de la convention de stage</title>
    <link rel="stylesheet" href="global_css.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
<?php display_header();?>
   
    <main>
    <div class="step-indicator">
            <div class="step"><span>1</span></div>
            <div class="step"><span>2</span></div>
            <div class="step"><span>3</span></div>
            <div class="step active"><span>4</span></div>
            <div class="step"><span>5</span></div>
            <div class="step"><span>6</span></div>
        </div>
        <section class="form-content">
            <h2>Sélectionner le signataire de la convention de stage</h2>
            <?php if ($error_message): ?>
                <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <form action="new_stage_step4.php" method="post">
                <div class="table-container">
                    <table class="stage-table">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Fixe</th>
                                <th>Fonction</th>
                                <th>Choisir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contacts as $contact): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($contact['titreContact']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['nomContact']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['prenomContact']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['mailContact']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['mobileContact']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['fixeContact']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['fonctionContact']); ?></td>
                                    <td>
                                        <input type="radio" name="gerant_id" value="<?php echo htmlspecialchars($contact['idContact']); ?>" <?php echo ($currentGerantId == $contact['idContact']) ? 'checked' : ''; ?> required>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="form-buttons">
                    <button type="button" onclick="window.location.href='new_stage_step3.php'">Retour</button>
                    <button type="submit">Suivant</button>
                </div>
            </form>
        </section>
    </main>
    <?php display_footer(); ?>
    <?php loading(); ?>
</body>

</html>
