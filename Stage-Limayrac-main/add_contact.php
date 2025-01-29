<?php
session_start();
include 'functions.php'; // Inclure les fonctions nécessaires

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Vérifie si les informations de l'entreprise sont disponibles dans la session
if (!isset($_SESSION['siren']) || !isset($_SESSION['nic'])) {
    header("Location: new_stage_step2.php");
    exit;
}

$success_message = "";
$error_message = "";

// Vérifie si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titreContact = $_POST['titreContact'];
    $nomContact = $_POST['nomContact'];
    $prenomContact = $_POST['prenomContact'];
    $mailContact = $_POST['mailContact'];
    $mobileContact = $_POST['mobileContact'];
    $fixeContact = $_POST['fixeContact'];
    $estActifContact = isset($_POST['estActifContact']) ? 1 : 0;
    $fonctionContact = $_POST['fonctionContact'];
    $siren = $_SESSION['siren'];
    $nic = $_SESSION['nic'];

    // Connexion à la base de données
    $conn = connexion();

    // Requête pour insérer les données du nouveau contact
    $sql_insert = "INSERT INTO contact (titreContact, nomContact, prenomContact, mailContact, mobileContact, fixeContact, estActifContact, fonctionContact, SIREN, NIC) 
                   VALUES (:titreContact, :nomContact, :prenomContact, :mailContact, :mobileContact, :fixeContact, :estActifContact, :fonctionContact, :siren, :nic)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bindParam(':titreContact', $titreContact, PDO::PARAM_STR);
    $stmt_insert->bindParam(':nomContact', $nomContact, PDO::PARAM_STR);
    $stmt_insert->bindParam(':prenomContact', $prenomContact, PDO::PARAM_STR);
    $stmt_insert->bindParam(':mailContact', $mailContact, PDO::PARAM_STR);
    $stmt_insert->bindParam(':mobileContact', $mobileContact, PDO::PARAM_STR);
    $stmt_insert->bindParam(':fixeContact', $fixeContact, PDO::PARAM_STR);
    $stmt_insert->bindParam(':estActifContact', $estActifContact, PDO::PARAM_INT);
    $stmt_insert->bindParam(':fonctionContact', $fonctionContact, PDO::PARAM_STR);
    $stmt_insert->bindParam(':siren', $siren, PDO::PARAM_STR);
    $stmt_insert->bindParam(':nic', $nic, PDO::PARAM_STR);

    if ($stmt_insert->execute()) {
        $success_message = "Le contact a été ajouté avec succès.";
        header("Location: new_stage_step3.php");
        exit();
    } else {
        $error_message = "Une erreur s'est produite lors de l'ajout du contact.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Contact</title>
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
    <main>
        <div class="form-content">
            <h2>Ajouter un Contact</h2>
            <?php if ($error_message): ?>
                <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <p class="success"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>
            <form action="add_contact.php" method="post">
                <div class="form-group">
                    <label for="titreContact">Titre :</label>
                    <input type="text" id="titreContact" name="titreContact" required>
                </div>
                <div class="form-group">
                    <label for="nomContact">Nom :</label>
                    <input type="text" id="nomContact" name="nomContact" required>
                </div>
                <div class="form-group">
                    <label for="prenomContact">Prénom :</label>
                    <input type="text" id="prenomContact" name="prenomContact" required>
                </div>
                <div class="form-group">
                    <label for="mailContact">Email :</label>
                    <input type="email" id="mailContact" name="mailContact" required>
                </div>
                <div class="form-group">
                    <label for="mobileContact">Mobile :</label>
                    <input type="text" id="mobileContact" name="mobileContact">
                </div>
                <div class="form-group">
                    <label for="fixeContact">Fixe :</label>
                    <input type="text" id="fixeContact" name="fixeContact">
                </div>
                <div class="form-group">
                    <label for="fonctionContact">Fonction :</label>
                    <input type="text" id="fonctionContact" name="fonctionContact">
                </div>
                <div class="form-group">
                    <label for="estActifContact">Actif :</label>
                    <input type="checkbox" id="estActifContact" name="estActifContact" checked>
                </div>
                <div class="form-buttons">
                    <button type="button" onclick="window.location.href='new_stage_step3.php'">Retour</button>
                    <button type="submit">Ajouter</button>
                </div>
            </form>
        </div>
    </main>
    <?php display_footer(); ?>
    <?php loading(); ?>
</body>
</html>
