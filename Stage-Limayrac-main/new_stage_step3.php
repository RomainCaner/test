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

$siret = $_SESSION['siret'];
$siren = $_SESSION['siren'];
$nic = $_SESSION['nic'];
$etablissement = $_SESSION['etablissement'];
$success_message = "";
$error_message = "";

// Connexion à la base de données
$conn = connexion();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Commence une transaction
        $conn->beginTransaction();

        $siren = substr($siret, 0, 9);
        $nic = substr($siret, 9, 5);
        $denominationUniteLegale = $etablissement['uniteLegale']['denominationUniteLegale'] ?? null;
        $numAdrEtab = $etablissement['adresseEtablissement']['numeroVoieEtablissement'] ?? null;
        $voieAdrEtab = $etablissement['adresseEtablissement']['typeVoieEtablissement'] ?? null;
        $libAdrEtab = $etablissement['adresseEtablissement']['libelleVoieEtablissement'] ?? null;
        $cpAdrEtab = $etablissement['adresseEtablissement']['codePostalEtablissement'] ?? null;
        $villeAdrEtab = $etablissement['adresseEtablissement']['libelleCommuneEtablissement'] ?? null;
        $missionEtab = $etablissement['uniteLegale']['activitePrincipaleUniteLegale'] ?? null;
        $fixeEtab = $etablissement['telephone'] ?? null;
        $mailEtab = $etablissement['email'] ?? null;
        $estSiegeSocialEtab = isset($etablissement['etablissementSiege']) && $etablissement['etablissementSiege'] ? '1' : '0';

        // Vérifie si l'organisation existe déjà
        $sql_check_organisation = "SELECT COUNT(*) FROM organisation WHERE SIREN = :siren";
        $stmt_check_organisation = $conn->prepare($sql_check_organisation);
        $stmt_check_organisation->execute([':siren' => $siren]);
        $organisation_exists = $stmt_check_organisation->fetchColumn() > 0;

        if (!$organisation_exists) {
            // Insertion de l'organisation
            $sql_organisation = "INSERT INTO organisation (SIREN, denominationOrg, formeJuridiqueOrg, codeApeOrg, dateCreationOrg, trancheEffectifsOrg) VALUES (:siren, :denomination, :formeJuridique, :codeApe, :dateCreation, :trancheEffectifs)";
            $stmt_organisation = $conn->prepare($sql_organisation);
            $stmt_organisation->execute([
                ':siren' => $siren,
                ':denomination' => $denominationUniteLegale,
                ':formeJuridique' => $etablissement['uniteLegale']['categorieJuridiqueUniteLegale'] ?? null,
                ':codeApe' => $missionEtab,
                ':dateCreation' => $etablissement['uniteLegale']['dateCreationUniteLegale'] ?? null,
                ':trancheEffectifs' => $etablissement['uniteLegale']['trancheEffectifsUniteLegale'] ?? null
            ]);
        }

        // Vérifie si l'établissement existe déjà
        $sql_check_etablissement = "SELECT COUNT(*) FROM etablissement WHERE SIREN = :siren AND NIC = :nic";
        $stmt_check_etablissement = $conn->prepare($sql_check_etablissement);
        $stmt_check_etablissement->execute([':siren' => $siren, ':nic' => $nic]);
        $etablissement_exists = $stmt_check_etablissement->fetchColumn() > 0;

        if (!$etablissement_exists) {
            // Insertion de l'établissement
            $sql_etablissement = "INSERT INTO etablissement (SIREN, NIC, denominationEtab, numAdrEtab, voieAdrEtab, libAdrEtab, cpAdrEtab, villeAdrEtab, missionEtab, fixeEtab, mailEtab, estSiegeSocialEtab) VALUES (:siren, :nic, :denomination, :numAdr, :voieAdr, :libAdr, :cpAdr, :villeAdr, :mission, :fixe, :mail, :estSiegeSocial)";
            $stmt_etablissement = $conn->prepare($sql_etablissement);
            $stmt_etablissement->execute([
                ':siren' => $siren,
                ':nic' => $nic,
                ':denomination' => $denominationUniteLegale,
                ':numAdr' => $numAdrEtab,
                ':voieAdr' => $voieAdrEtab,
                ':libAdr' => $libAdrEtab,
                ':cpAdr' => $cpAdrEtab,
                ':villeAdr' => $villeAdrEtab,
                ':mission' => $missionEtab,
                ':fixe' => $fixeEtab,
                ':mail' => $mailEtab,
                ':estSiegeSocial' => $estSiegeSocialEtab
            ]);
        }

        // Commit la transaction
        $conn->commit();
        
        // Redirection vers l'étape suivante
        header("Location: new_stage_step3.php");
        exit;
    } catch (Exception $e) {
        // Rollback la transaction en cas d'erreur
        $conn->rollBack();
        $error_message = "Erreur lors de l'enregistrement de l'établissement : " . $e->getMessage();
    }
}

// Requête pour récupérer tous les contacts associés à l'entreprise
$sql_contacts = "SELECT * FROM contact WHERE SIREN = :siren AND NIC = :nic";
$stmt_contacts = $conn->prepare($sql_contacts);
$stmt_contacts->bindParam(':siren', $siren, PDO::PARAM_STR);
$stmt_contacts->bindParam(':nic', $nic, PDO::PARAM_STR);
$stmt_contacts->execute();
$contacts = $stmt_contacts->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Étape 3 : Contacts</title>
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
            <div class="step active"><span>3</span></div>
            <div class="step"><span>4</span></div>
            <div class="step"><span>5</span></div>
            <div class="step"><span>6</span></div>
        </div>
        <section class="form-content">
            <h2>Contacts du stage</h2>
            <?php if ($error_message): ?>
                <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <p class="success"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>
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
                            <th>Action</th>
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
                                    <a class="download-link" href="edit_contact.php?idContact=<?php echo $contact['idContact']; ?>">Modifier</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p>Vous ne trouvez le signataire de la convention et/ou votre maître de stage ? <a href="add_contact.php">Ajouter</a></p>
            <div class="form-buttons">
                <button type="button" onclick="window.location.href='new_stage_step2.php'">Retour</button>
                <form action="new_stage_step4.php" method="post" style="display:inline;">
                    <button type="submit">Suivant</button>
                </form>
            </div>
        </section>
    </main>
    <?php display_footer(); ?>
    <?php loading(); ?>
</body>
</html>
