<?php
session_start();
include 'functions.php'; // Inclure les fonctions nécessaires

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login_selection.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Connexion à la base de données
$conn = connexion();

// Requête pour récupérer les informations de l'utilisateur
$sql = "SELECT utilisateur.prenomUser, utilisateur.nomUser, utilisateur.mailUser, utilisateur.fixeUser, utilisateur.mobileUser, 
               etudiant.numAdrEtudiant, etudiant.voieAdrEtudiant, etudiant.libAdrEtudiant, etudiant.cpAdrEtudiant, etudiant.villeAdrEtudiant 
        FROM utilisateur 
        JOIN etudiant ON utilisateur.idUser = etudiant.idEtudiant
        WHERE utilisateur.idUser = :user_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user_info) {
    echo "Erreur : utilisateur non trouvé.";
    exit;
}

// Si le formulaire est soumis, mettre à jour les informations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mailUser = $_POST['mailUser'];
    $fixeUser = $_POST['fixeUser'];
    $mobileUser = $_POST['mobileUser'];
    $numAdrEtudiant = $_POST['numAdrEtudiant'];
    $voieAdrEtudiant = $_POST['voieAdrEtudiant'];
    $libAdrEtudiant = $_POST['libAdrEtudiant'];
    $cpAdrEtudiant = $_POST['cpAdrEtudiant'];
    $villeAdrEtudiant = $_POST['villeAdrEtudiant'];

    $sql_update = "UPDATE utilisateur 
                   JOIN etudiant ON utilisateur.idUser = etudiant.idEtudiant 
                   SET utilisateur.mailUser = :mailUser, utilisateur.fixeUser = :fixeUser, utilisateur.mobileUser = :mobileUser, 
                       etudiant.numAdrEtudiant = :numAdrEtudiant, etudiant.voieAdrEtudiant = :voieAdrEtudiant, etudiant.libAdrEtudiant = :libAdrEtudiant, 
                       etudiant.cpAdrEtudiant = :cpAdrEtudiant, etudiant.villeAdrEtudiant = :villeAdrEtudiant 
                   WHERE utilisateur.idUser = :user_id";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bindParam(':mailUser', $mailUser);
    $stmt_update->bindParam(':fixeUser', $fixeUser);
    $stmt_update->bindParam(':mobileUser', $mobileUser);
    $stmt_update->bindParam(':numAdrEtudiant', $numAdrEtudiant);
    $stmt_update->bindParam(':voieAdrEtudiant', $voieAdrEtudiant);
    $stmt_update->bindParam(':libAdrEtudiant', $libAdrEtudiant);
    $stmt_update->bindParam(':cpAdrEtudiant', $cpAdrEtudiant);
    $stmt_update->bindParam(':villeAdrEtudiant', $villeAdrEtudiant);
    $stmt_update->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_update->execute();

    header("Location: new_stage_step1.php");
    exit;
}

// Prépare les informations pour affichage
$prenomUser = $user_info['prenomUser'];
$nomUser = $user_info['nomUser'];
$mailUser = $user_info['mailUser'];
$fixeUser = $user_info['fixeUser'];
$mobileUser = $user_info['mobileUser'];
$numAdrEtudiant = $user_info['numAdrEtudiant'];
$voieAdrEtudiant = $user_info['voieAdrEtudiant'];
$libAdrEtudiant = $user_info['libAdrEtudiant'];
$cpAdrEtudiant = $user_info['cpAdrEtudiant'];
$villeAdrEtudiant = $user_info['villeAdrEtudiant'];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Profil</title>
    <link rel="stylesheet" href="global_css.css"> <!-- Remplacé main.css par global_css.css -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>

<body>
<?php display_header();?>

 

    <main>
        <section class="form-content">
            <h2>Modifier Profil</h2>
            <form action="edit_profile.php" method="post">
                <div class="form-group">
                    <label for="mailUser">Email :</label>
                    <input type="email" id="mailUser" name="mailUser" value="<?php echo htmlspecialchars($mailUser ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="fixeUser">Téléphone Fixe :</label>
                    <input type="text" id="fixeUser" name="fixeUser" value="<?php echo htmlspecialchars($fixeUser ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="mobileUser">Téléphone Mobile :</label>
                    <input type="text" id="mobileUser" name="mobileUser" value="<?php echo htmlspecialchars($mobileUser ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="numAdrEtudiant">Numéro :</label>
                    <input type="text" id="numAdrEtudiant" name="numAdrEtudiant" value="<?php echo htmlspecialchars($numAdrEtudiant ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="voieAdrEtudiant">Voie :</label>
                    <input type="text" id="voieAdrEtudiant" name="voieAdrEtudiant" value="<?php echo htmlspecialchars($voieAdrEtudiant ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="libAdrEtudiant">Libellé :</label>
                    <input type="text" id="libAdrEtudiant" name="libAdrEtudiant" value="<?php echo htmlspecialchars($libAdrEtudiant ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="cpAdrEtudiant">Code Postal :</label>
                    <input type="text" id="cpAdrEtudiant" name="cpAdrEtudiant" value="<?php echo htmlspecialchars($cpAdrEtudiant ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="villeAdrEtudiant">Ville :</label>
                    <input type="text" id="villeAdrEtudiant" name="villeAdrEtudiant" value="<?php echo htmlspecialchars($villeAdrEtudiant ?? ''); ?>">
                </div>
                <div class="form-buttons">
                    <button type="submit">Enregistrer</button>
                </div>
            </form>
        </section>
    </main>
    <?php display_footer(); ?>
</body>

</html>
