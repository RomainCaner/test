<?php
session_start();
include 'functions.php';

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login_selection.php");
    exit;
}

// --- --- Test de l'API --- --- //
$test_siret = "44306184100047"; // Google France
$accessToken = 'a79979c1-ed2e-3d88-849c-65ef5f3088c2';
// --- --- Fin Test de l'API --- --- //

$success_message = "";
$error_message = "";
$etablissement = null;
$form_submitted = false;

// Connexion à la base de données
$conn = connexion();

// Vérifie si on est en mode modification de stage
$idStage = isset($_SESSION['idStage']) ? $_SESSION['idStage'] : null;

// Initialiser $siretFromDB
$siretFromDB = '';

// Si en mode édition, récupérer le SIRET depuis la base de données
if ($idStage) {
    $siretFromDB = getSiretByIdStage($conn, $idStage);
}

// Vérifie si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_submitted = true;
    // Supprimer tous les espaces du SIRET saisi
    $siret = isset($_POST['siret']) ? preg_replace('/\s+/', '', trim($_POST['siret'])) : '';

    if (strlen($siret) !== 14 || !ctype_digit($siret)) {
        $error_message = "Le SIRET doit comporter 14 chiffres.";
    } else {
        // Appeler la fonction externe pour récupérer et insérer les données
        $result = fetchAndInsertEtablissement($siret, $accessToken, $conn);

        if ($result['success']) {
            $etablissement = $result['etablissement'];
            $success_message = $result['message'];
        } else {
            $error_message = $result['error'];
        }

        if ($result['success']) {
            // Enregistrer les informations dans la session
            $_SESSION['siret'] = $siret;
            $_SESSION['siren'] = substr($siret, 0, 9);
            $_SESSION['nic'] = substr($siret, 9, 5);
            $_SESSION['etablissement'] = $etablissement;
        }
    }
}

// Récupération du SIRET depuis le GET, la base de données ou la session
if (isset($_GET['siret'])) {
    $siret = preg_replace('/\s+/', '', trim($_GET['siret']));
    $_SESSION['siret'] = $siret; // Stocker dans la session
} elseif ($idStage && $siretFromDB) {
    $siret = $siretFromDB;
    $_SESSION['siret'] = $siret; // Assurer que la session contient le SIRET
} else {
    $siret = isset($_SESSION['siret']) ? $_SESSION['siret'] : '';
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Étape 2 : Informations sur l'entreprise</title>
    <link rel="stylesheet" href="global_css.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>

<body>
    <?php display_header(); ?>
    <?php //verifierEtatAPI_SIRENE($test_siret, $accessToken); 
    ?>
    <main>
        <div class="step-indicator">
            <div class="step"><span>1</span></div>
            <div class="step active"><span>2</span></div>
            <div class="step"><span>3</span></div>
            <div class="step"><span>4</span></div>
            <div class="step"><span>5</span></div>
            <div class="step"><span>6</span></div>
        </div>
        <section class="form-content">
            <h2>Informations sur l'entreprise</h2>
            <div class="notification-container">
                <?php if ($form_submitted && $error_message): ?>
                    <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <p class="success"><?php echo htmlspecialchars($success_message); ?></p>
                <?php endif; ?>
            </div>
            <form action="new_stage_step2.php" method="post">
                <div class="form-group">
                    <label for="siret">Numéro SIRET :</label>
                    <input type="text" id="siret" name="siret" value="<?php echo htmlspecialchars($siret); ?>" required>
                </div>
                <div class="form-buttons">
                    <?php if (!$etablissement): ?>
                        <button type="button" onclick="window.location.href='new_stage_step1.php'">Retour</button>
                    <?php endif; ?>
                    <button type="submit">Rechercher</button>
                </div>
            </form>

            <?php if ($etablissement): ?>
                <div class="etablissement-info">
                    <h3>Informations de l'établissement</h3>
                    <table>
                        <tr>
                            <th>Dénomination</th>
                            <td><?php echo htmlspecialchars($etablissement['denominationEtab'] ?? ($etablissement['uniteLegale']['denominationUniteLegale'] ?? 'N/A')); ?></td>
                        </tr>
                        <tr>
                            <th>Numéro d'adresse</th>
                            <td><?php echo htmlspecialchars($etablissement['numAdrEtab'] ?? ($etablissement['adresseEtablissement']['numeroVoieEtablissement'] ?? 'N/A')); ?></td>
                        </tr>
                        <tr>
                            <th>Voie</th>
                            <td><?php echo htmlspecialchars($etablissement['voieAdrEtab'] ?? ($etablissement['adresseEtablissement']['typeVoieEtablissement'] ?? 'N/A')); ?></td>
                        </tr>
                        <tr>
                            <th>Libellé de l'adresse</th>
                            <td><?php echo htmlspecialchars($etablissement['libAdrEtab'] ?? ($etablissement['adresseEtablissement']['libelleVoieEtablissement'] ?? 'N/A')); ?></td>
                        </tr>
                        <tr>
                            <th>Code Postal</th>
                            <td><?php echo htmlspecialchars($etablissement['cpAdrEtab'] ?? ($etablissement['adresseEtablissement']['codePostalEtablissement'] ?? 'N/A')); ?></td>
                        </tr>
                        <tr>
                            <th>Ville</th>
                            <td><?php echo htmlspecialchars($etablissement['villeAdrEtab'] ?? ($etablissement['adresseEtablissement']['libelleCommuneEtablissement'] ?? 'N/A')); ?></td>
                        </tr>
                        <tr>
                            <th>Mission (code APE)</th>
                            <td><?php echo htmlspecialchars($etablissement['missionEtab'] ?? ($etablissement['uniteLegale']['activitePrincipaleUniteLegale'] ?? 'N/A')); ?></td>
                        </tr>
                        <tr>
                            <th>Téléphone</th>
                            <td><?php echo htmlspecialchars($etablissement['fixeEtab'] ?? ($etablissement['telephone'] ?? 'N/A')); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($etablissement['mailEtab'] ?? ($etablissement['email'] ?? 'N/A')); ?></td>
                        </tr>
                        <tr>
                            <th>Est Siège Social</th>
                            <td>
                                <?php
                                if (isset($etablissement['estSiegeSocialEtab'])) {
                                    echo $etablissement['estSiegeSocialEtab'] ? 'Oui' : 'Non';
                                } elseif (isset($etablissement['etablissementSiege'])) {
                                    echo $etablissement['etablissementSiege'] ? 'Oui' : 'Non';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="form-buttons">
                    <button type="button" onclick="window.location.href='new_stage_step1.php'">Retour</button>
                    <!-- Bouton "Modifier" -->
                    <form action="modifier_etablissement.php" method="get" style="display:inline;">
                        <input type="hidden" name="siret" value="<?php echo htmlspecialchars($siret); ?>">
                        <button type="submit">Modifier</button>
                    </form>
                    <!-- Bouton "Confirmer" -->
                    <form action="new_stage_step3.php" method="post" style="display:inline;">
                        <button type="submit">Confirmer</button>
                    </form>


                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php display_footer(); ?>
    <?php loading(); ?>
</body>

</html>