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

// Requête pour récupérer les informations de l'utilisateur avec l'année scolaire active
$sql = "SELECT utilisateur.prenomUser, utilisateur.nomUser, utilisateur.mailUser, utilisateur.fixeUser, utilisateur.mobileUser, 
               etudiant.numAdrEtudiant, etudiant.voieAdrEtudiant, etudiant.libAdrEtudiant, etudiant.cpAdrEtudiant, etudiant.villeAdrEtudiant, 
               inscription.idClasse, anneeScolaire.libAnneeScolaire
        FROM utilisateur 
        JOIN etudiant ON utilisateur.idUser = etudiant.idEtudiant
        JOIN inscription ON etudiant.idEtudiant = inscription.idEtudiant 
        JOIN classe ON inscription.idClasse = classe.idClasse
        JOIN anneeScolaire ON inscription.idAnneeScolaire = anneeScolaire.idAnneeScolaire
        WHERE utilisateur.idUser = :user_id
          AND anneeScolaire.estActiveAnneeScolaire = 1"; 
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user_info) {
    echo "Erreur : utilisateur non trouvé.";
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
$idClasse = $user_info['idClasse'];
$libAnneeScolaire = $user_info['libAnneeScolaire'];

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Stage - Étape 1</title>
    <link rel="stylesheet" href="global_css.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>

<body>
    <?php display_header(); ?>

    <main>
        <div class="step-indicator">
            <div class="step active"><span>1</span></div>
            <div class="step"><span>2</span></div>
            <div class="step"><span>3</span></div>
            <div class="step"><span>4</span></div>
            <div class="step"><span>5</span></div>
            <div class="step"><span>6</span></div>
        </div>

        <section class="form-content">
            <h2>Informations sur l'élève</h2>
            <div class="form-group">
                <label>Prénom :</label>
                <span><?php echo htmlspecialchars($prenomUser ?? ''); ?></span>
            </div>
            <div class="form-group">
                <label>Nom :</label>
                <span><?php echo htmlspecialchars($nomUser ?? ''); ?></span>
            </div>
            <div class="form-group">
                <label>Classe :</label>
                <span><?php echo htmlspecialchars($idClasse ?? ''); ?></span>
            </div>
            <div class="form-group">
                <label>Année Scolaire :</label>
                <span><?php echo htmlspecialchars($libAnneeScolaire ?? ''); ?></span>
            </div>
            <div class="form-group">
                <label>Email :</label>
                <span><?php echo htmlspecialchars($mailUser ?? ''); ?></span>
            </div>
            <div class="form-group">
                <label>Téléphone Fixe :</label>
                <span><?php echo htmlspecialchars($fixeUser ?? ''); ?></span>
            </div>
            <div class="form-group">
                <label>Téléphone Mobile :</label>
                <span><?php echo htmlspecialchars($mobileUser ?? ''); ?></span>
            </div>
            <div class="form-group">
                <label>Numéro de rue :</label>
                <span><?php echo htmlspecialchars($numAdrEtudiant ?? ''); ?></span>
            </div>
            <div class="form-group">
                <label>Nom de rue :</label>
                <span><?php echo htmlspecialchars($voieAdrEtudiant ?? ''); ?></span>
            </div>
            <div class="form-group">
                <label>Complément d'adresse :</label>
                <span><?php echo htmlspecialchars($libAdrEtudiant ?? ''); ?></span>
            </div>
            <div class="form-group">
                <label>Code Postal :</label>
                <span><?php echo htmlspecialchars($cpAdrEtudiant ?? ''); ?></span>
            </div>
            <div class="form-group">
                <label>Ville :</label>
                <span><?php echo htmlspecialchars($villeAdrEtudiant ?? ''); ?></span>
            </div>
            <div class="form-buttons">
                <form action="new_stage_step2.php">
                    <button type="submit">Suivant</button>
                </form>
            </div>
            <div class="edit-profile-link">
                <a href="edit_profile.php">Les informations sont erronées ? Modifier</a>
            </div>
        </section>
    </main>

    <?php display_footer(); ?>
    <?php loading(); ?>
</body>
</html>