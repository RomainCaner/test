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

// Vérifie si le gérant a été sélectionné à l'étape précédente
if (!isset($_SESSION['gerant_id'])) {
    header("Location: new_stage_step4.php");
    exit;
}

$siren = $_SESSION['siren'];
$nic = $_SESSION['nic'];
$success_message = "";
$error_message = "";
$currentTuteurId = null;

// Connexion à la base de données
$conn = connexion();

// Requête pour récupérer tous les contacts associés à l'entreprise
$sql_contacts = "SELECT * FROM contact WHERE SIREN = :siren AND NIC = :nic";
$stmt_contacts = $conn->prepare($sql_contacts);
$stmt_contacts->bindParam(':siren', $siren, PDO::PARAM_STR);
$stmt_contacts->bindParam(':nic', $nic, PDO::PARAM_STR);
$stmt_contacts->execute();
$contacts = $stmt_contacts->fetchAll(PDO::FETCH_ASSOC);

// Vérifie si on est en mode modification de stage
$idStage = isset($_SESSION['idStage']) ? (int)$_SESSION['idStage'] : null;

if ($idStage) {
    // Requête pour récupérer l'idMaitreDeStage depuis la table stage
    $sql_stage = "SELECT idMaitreDeStage FROM stage WHERE idStage = :idStage";
    $stmt_stage = $conn->prepare($sql_stage);
    $stmt_stage->bindParam(':idStage', $idStage, PDO::PARAM_INT);
    $stmt_stage->execute();
    $stage = $stmt_stage->fetch(PDO::FETCH_ASSOC);
    
    if ($stage && isset($stage['idMaitreDeStage'])) {
        $currentTuteurId = (int)$stage['idMaitreDeStage'];
    } else {
        // Optionnel : gérer le cas où aucun tuteur n'est trouvé
        $currentTuteurId = null;
    }
}

// Vérifie si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['idContact'])) {
        $tuteur_id = $_POST['idContact'];
        $_SESSION['tuteur_id'] = $tuteur_id;
        header("Location: new_stage_step6.php");
        exit();
    } else {
        $error_message = "Veuillez sélectionner un tuteur.";
    }
}


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Étape 5 : Sélectionner le maître de stage</title>
    <link rel="stylesheet" href="global_css.css"> <!-- Remplacé debug.css par global_css.css -->
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
            <div class="step"><span>4</span></div>
            <div class="step active"><span>5</span></div>
            <div class="step"><span>6</span></div>
        </div>
        <section class="form-content">
            <h2>Sélectionner le maître de stage</h2>
            <?php if ($error_message): ?>
                <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <p class="success"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>
            <form action="new_stage_step5.php" method="post">
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
                                        <input type="radio" name="idContact" value="<?php echo htmlspecialchars($contact['idContact']); ?>" <?php echo ($currentTuteurId === (int)$contact['idContact']) ? 'checked' : ''; ?> required>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="form-buttons">
                    <button type="button" onclick="window.location.href='new_stage_step4.php'">Retour</button>
                    <button type="submit">Suivant</button>
                </div>
            </form>
        </section>
    </main>
    <?php display_footer(); ?>
    <?php loading(); ?>
</body>

</html>
