<?php
session_start();
include 'functions.php'; // Inclure les fonctions nécessaires

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login_selection.php");
    exit;
}

$contact_id = isset($_GET['idContact']) ? $_GET['idContact'] : null;

if (!$contact_id) {
    header("Location: new_stage_step3.php");
    exit;
}

$success_message = "";
$error_message = "";

// Connexion à la base de données
$conn = connexion();

// Requête pour récupérer les informations du contact
$sql_contact = "SELECT * FROM contact WHERE idContact = :idContact";
$stmt_contact = $conn->prepare($sql_contact);
$stmt_contact->bindParam(':idContact', $contact_id, PDO::PARAM_INT);
$stmt_contact->execute();
$contact = $stmt_contact->fetch(PDO::FETCH_ASSOC);

if (!$contact) {
    echo "Erreur : contact non trouvé.";
    exit;
}

// Vérifie si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titreContact     = $_POST['titreContact'];
    $nomContact       = $_POST['nomContact'];
    $prenomContact    = $_POST['prenomContact'];
    $mailContact      = $_POST['mailContact'];
    $mobileContact    = $_POST['mobileContact'];
    $fixeContact      = $_POST['fixeContact'];
    $fonctionContact  = $_POST['fonctionContact'];
    // Gère la case à cocher "Actif"
    $estActifContact  = isset($_POST['estActifContact']) ? 1 : 0;

    // Requête pour mettre à jour les informations du contact
    $sql_update = "
        UPDATE contact
        SET 
            titreContact     = :titreContact,
            nomContact       = :nomContact,
            prenomContact    = :prenomContact,
            mailContact      = :mailContact,
            mobileContact    = :mobileContact,
            fixeContact      = :fixeContact,
            fonctionContact  = :fonctionContact,
            estActifContact  = :estActifContact
        WHERE idContact = :idContact
    ";

    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bindParam(':titreContact', $titreContact, PDO::PARAM_STR);
    $stmt_update->bindParam(':nomContact', $nomContact, PDO::PARAM_STR);
    $stmt_update->bindParam(':prenomContact', $prenomContact, PDO::PARAM_STR);
    $stmt_update->bindParam(':mailContact', $mailContact, PDO::PARAM_STR);
    $stmt_update->bindParam(':mobileContact', $mobileContact, PDO::PARAM_STR);
    $stmt_update->bindParam(':fixeContact', $fixeContact, PDO::PARAM_STR);
    $stmt_update->bindParam(':fonctionContact', $fonctionContact, PDO::PARAM_STR);
    $stmt_update->bindParam(':estActifContact', $estActifContact, PDO::PARAM_INT);
    $stmt_update->bindParam(':idContact', $contact_id, PDO::PARAM_INT);

    if ($stmt_update->execute()) {
        $success_message = "Le contact a été mis à jour avec succès.";
        header("Location: new_stage_step3.php");
        exit;
    } else {
        $error_message = "Une erreur s'est produite lors de la mise à jour du contact.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Contact</title>
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
            <h2>Modifier un Contact</h2>

            <?php if ($error_message): ?>
                <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <p class="success"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>

            <form action="edit_contact.php?idContact=<?php echo $contact_id; ?>" method="post">
                <div class="form-group">
                    <label for="titreContact">Titre :</label>
                    <input 
                        type="text" 
                        id="titreContact" 
                        name="titreContact" 
                        value="<?php echo htmlspecialchars($contact['titreContact']); ?>" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="nomContact">Nom :</label>
                    <input 
                        type="text" 
                        id="nomContact" 
                        name="nomContact" 
                        value="<?php echo htmlspecialchars($contact['nomContact']); ?>" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="prenomContact">Prénom :</label>
                    <input 
                        type="text" 
                        id="prenomContact" 
                        name="prenomContact" 
                        value="<?php echo htmlspecialchars($contact['prenomContact']); ?>" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="mailContact">Email :</label>
                    <input 
                        type="email" 
                        id="mailContact" 
                        name="mailContact" 
                        value="<?php echo htmlspecialchars($contact['mailContact']); ?>" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="mobileContact">Mobile :</label>
                    <input 
                        type="text" 
                        id="mobileContact" 
                        name="mobileContact" 
                        value="<?php echo htmlspecialchars($contact['mobileContact']); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="fixeContact">Fixe :</label>
                    <input 
                        type="text" 
                        id="fixeContact" 
                        name="fixeContact" 
                        value="<?php echo htmlspecialchars($contact['fixeContact']); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="fonctionContact">Fonction :</label>
                    <input 
                        type="text" 
                        id="fonctionContact" 
                        name="fonctionContact" 
                        value="<?php echo htmlspecialchars($contact['fonctionContact']); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="estActifContact">Actif :</label>
                    <input 
                        type="checkbox" 
                        id="estActifContact" 
                        name="estActifContact"
                        <?php echo ($contact['estActifContact'] == 1) ? 'checked' : ''; ?>
                    >
                </div>

                <div class="form-buttons">
                    <button type="button" onclick="window.location.href='new_stage_step3.php'">Retour</button>
                    <button type="submit">Enregistrer</button>
                </div>
            </form>
        </div>
    </main>

    <?php display_footer(); ?>
    <?php loading(); ?>
</body>
</html>
