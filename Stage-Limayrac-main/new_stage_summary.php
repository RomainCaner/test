<?php
session_start();
include 'functions.php'; // Inclure les fonctions nécessaires

// Activer les rapports d'erreurs pendant le développement (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login_selection.php");
    exit;
}

// Vérifie si les informations nécessaires sont disponibles dans la session
if (!isset($_SESSION['siren']) || !isset($_SESSION['nic']) || !isset($_SESSION['tuteur_id']) || !isset($_SESSION['gerant_id'])) {
    header("Location: new_stage_step5.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$siren = $_SESSION['siren'];
$nic = $_SESSION['nic'];
$tuteur_id = $_SESSION['tuteur_id'];
$gerant_id = $_SESSION['gerant_id'];

// Connexion à la base de données
$conn = connexion();

// Vérifier si un stage existe déjà pour cet étudiant
$sql_check_stage = "SELECT * FROM stage WHERE idEtudiant = :idEtudiant ORDER BY idStage DESC LIMIT 1";
$stmt_check_stage = $conn->prepare($sql_check_stage);
$stmt_check_stage->bindParam(':idEtudiant', $user_id, PDO::PARAM_INT);

$stage = false;
$idStage = null;

// Exécuter la requête et récupérer le stage
if ($stmt_check_stage->execute()) {
    $stage = $stmt_check_stage->fetch(PDO::FETCH_ASSOC);

    if ($stage) {
        // Si un stage existe, récupérer son id
        $idStage = $stage['idStage'];
    } else {
        // Aucun stage trouvé pour cet étudiant
        die("Aucun stage trouvé pour cet étudiant. Veuillez créer un stage avant de continuer.");
    }
} else {
    // Erreur lors de l'exécution de la requête
    die("Erreur lors de la récupération des informations du stage.");
}

// Récupérer les informations de l'utilisateur
$sql_user = "SELECT utilisateur.prenomUser, utilisateur.nomUser, utilisateur.mailUser, utilisateur.fixeUser, utilisateur.mobileUser,
               etudiant.numAdrEtudiant, etudiant.voieAdrEtudiant, etudiant.libAdrEtudiant, etudiant.cpAdrEtudiant, etudiant.villeAdrEtudiant, classe.libClasse
        FROM utilisateur
        JOIN etudiant ON utilisateur.idUser = etudiant.idEtudiant
        JOIN inscription ON etudiant.idEtudiant = inscription.idEtudiant
        JOIN classe ON inscription.idClasse = classe.idClasse
        WHERE utilisateur.idUser = :user_id";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_user->execute();
$user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

// Récupérer les informations de l'entreprise
$sql_etablissement = "SELECT * FROM etablissement WHERE SIREN = :siren AND NIC = :nic";
$stmt_etablissement = $conn->prepare($sql_etablissement);
$stmt_etablissement->bindParam(':siren', $siren, PDO::PARAM_STR);
$stmt_etablissement->bindParam(':nic', $nic, PDO::PARAM_STR);
$stmt_etablissement->execute();
$etablissement = $stmt_etablissement->fetch(PDO::FETCH_ASSOC);

// Récupérer les informations du tuteur
$sql_tuteur = "SELECT * FROM contact WHERE idContact = :tuteur_id";
$stmt_tuteur = $conn->prepare($sql_tuteur);
$stmt_tuteur->bindParam(':tuteur_id', $tuteur_id, PDO::PARAM_INT);
$stmt_tuteur->execute();
$tuteur = $stmt_tuteur->fetch(PDO::FETCH_ASSOC);

// Récupérer les informations du gérant
$sql_gerant = "SELECT * FROM contact WHERE idContact = :gerant_id";
$stmt_gerant = $conn->prepare($sql_gerant);
$stmt_gerant->bindParam(':gerant_id', $gerant_id, PDO::PARAM_INT);
$stmt_gerant->execute();
$gerant = $stmt_gerant->fetch(PDO::FETCH_ASSOC);

// Mettre à jour l'étape de suivi lorsque l'utilisateur appuie sur "Terminer"
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résumé du Stage</title>
    <link rel="stylesheet" href="global_css.css"> <!-- Remplacé main.css et debug.css par global_css.css -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>

<body>
    <?php display_header(); ?>

    <main>
        <section class="form-content"> <!-- Ajout de la classe form-content -->
            <h2>Résumé du Stage</h2>
            <?php if (isset($error_message)): ?>
                <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>

            <div class="info-block">
                <h3>Profil de l'utilisateur</h3>
                <p><strong>Prénom :</strong> <?php echo htmlspecialchars($user_info['prenomUser'] ?? 'N/A'); ?></p>
                <p><strong>Nom :</strong> <?php echo htmlspecialchars($user_info['nomUser'] ?? 'N/A'); ?></p>
                <p><strong>Classe :</strong> <?php echo htmlspecialchars($user_info['libClasse'] ?? 'N/A'); ?></p>
                <p><strong>Email :</strong> <?php echo htmlspecialchars($user_info['mailUser'] ?? 'N/A'); ?></p>
                <p><strong>Téléphone Fixe :</strong> <?php echo htmlspecialchars($user_info['fixeUser'] ?? 'N/A'); ?></p>
                <p><strong>Téléphone Mobile :</strong> <?php echo htmlspecialchars($user_info['mobileUser'] ?? 'N/A'); ?></p>
                <p><strong>Adresse :</strong> <?php echo htmlspecialchars(
                                                    ($user_info['numAdrEtudiant'] ?? '') . ' ' .
                                                        ($user_info['voieAdrEtudiant'] ?? '') . ' ' .
                                                        ($user_info['libAdrEtudiant'] ?? '') . ', ' .
                                                        ($user_info['cpAdrEtudiant'] ?? '') . ' ' .
                                                        ($user_info['villeAdrEtudiant'] ?? '')
                                                ); ?></p>
            </div>

            <div class="info-block">
                <h3>Informations de l'établissement</h3>
                <p><strong>Dénomination :</strong> <?php echo htmlspecialchars($etablissement['denominationEtab'] ?? 'N/A'); ?></p>
                <p><strong>Numéro d'adresse :</strong> <?php echo htmlspecialchars($etablissement['numAdrEtab'] ?? 'N/A'); ?></p>
                <p><strong>Voie :</strong> <?php echo htmlspecialchars($etablissement['voieAdrEtab'] ?? 'N/A'); ?></p>
                <p><strong>Libellé de l'adresse :</strong> <?php echo htmlspecialchars($etablissement['libAdrEtab'] ?? 'N/A'); ?></p>
                <p><strong>Code Postal :</strong> <?php echo htmlspecialchars($etablissement['cpAdrEtab'] ?? 'N/A'); ?></p>
                <p><strong>Ville :</strong> <?php echo htmlspecialchars($etablissement['villeAdrEtab'] ?? 'N/A'); ?></p>
                <p><strong>Mission :</strong> <?php echo htmlspecialchars($etablissement['missionEtab'] ?? 'N/A'); ?></p>
                <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($etablissement['fixeEtab'] ?? 'N/A'); ?></p>
                <p><strong>Email :</strong> <?php echo htmlspecialchars($etablissement['mailEtab'] ?? 'N/A'); ?></p>
                <p><strong>Est Siège Social :</strong> <?php echo isset($etablissement['estSiegeSocialEtab']) ? ($etablissement['estSiegeSocialEtab'] ? 'Oui' : 'Non') : 'N/A'; ?></p>
            </div>

            <div class="info-block">
                <h3>Informations du Stage</h3>
                <p><strong>Date de Début :</strong> <?php echo htmlspecialchars($stage['dateDebutStage'] ?? 'N/A'); ?></p>
                <p><strong>Date de Fin :</strong> <?php echo htmlspecialchars($stage['dateFinStage'] ?? 'N/A'); ?></p>
                <p><strong>Durée Hebdomadaire :</strong> <?php echo htmlspecialchars($stage['dureeHebdoStage'] ?? 'N/A'); ?> heures</p>
                <p><strong>Activités :</strong> <?php echo htmlspecialchars($stage['activitesStage'] ?? 'N/A'); ?></p>
            </div>

            <?php if (!empty($stage['adrEtudiantStage'])): ?>
                <div class="info-block facultative">
                    <h3>Adresse du domicile de l'élève durant le stage</h3>
                    <p><strong>Adresse durant le stage :</strong> <?php echo htmlspecialchars($stage['adrEtudiantStage']); ?></p>
                </div>
            <?php endif; ?>

            <?php
            $hasLieuStageInfo = !empty($stage['nomLieuStage']) || !empty($stage['contactLieuStage']) || !empty($stage['adrLieuStage']);
            if ($hasLieuStageInfo):
            ?>
                <div class="info-block facultative">
                    <h3>Adresse du lieu de stage</h3>
                    <?php if (!empty($stage['nomLieuStage'])): ?>
                        <p><strong>Nom du Lieu de Stage :</strong> <?php echo htmlspecialchars($stage['nomLieuStage']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($stage['contactLieuStage'])): ?>
                        <p><strong>Contact du Lieu de Stage :</strong> <?php echo htmlspecialchars($stage['contactLieuStage']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($stage['adrLieuStage'])): ?>
                        <p><strong>Adresse du Lieu de Stage :</strong> <?php echo htmlspecialchars($stage['adrLieuStage']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>


            <div class="info-block">
                <h3>Maître de stage</h3>
                <p><strong>Nom :</strong> <?php echo htmlspecialchars(($tuteur['prenomContact'] ?? '') . ' ' . ($tuteur['nomContact'] ?? '')); ?></p>
                <p><strong>Email :</strong> <?php echo htmlspecialchars($tuteur['mailContact'] ?? 'N/A'); ?></p>
                <p><strong>Mobile :</strong> <?php echo htmlspecialchars($tuteur['mobileContact'] ?? 'N/A'); ?></p>
                <p><strong>Fixe :</strong> <?php echo htmlspecialchars($tuteur['fixeContact'] ?? 'N/A'); ?></p>
                <p><strong>Fonction :</strong> <?php echo htmlspecialchars($tuteur['fonctionContact'] ?? 'N/A'); ?></p>
                <p><strong>Actif :</strong> <?php echo isset($tuteur['estActifContact']) ? ($tuteur['estActifContact'] ? 'Oui' : 'Non') : 'N/A'; ?></p>
            </div>

            <div class="info-block">
                <h3>Signataire de la convention</h3>
                <p><strong>Nom :</strong> <?php echo htmlspecialchars(($gerant['prenomContact'] ?? '') . ' ' . ($gerant['nomContact'] ?? '')); ?></p>
                <p><strong>Email :</strong> <?php echo htmlspecialchars($gerant['mailContact'] ?? 'N/A'); ?></p>
                <p><strong>Mobile :</strong> <?php echo htmlspecialchars($gerant['mobileContact'] ?? 'N/A'); ?></p>
                <p><strong>Fixe :</strong> <?php echo htmlspecialchars($gerant['fixeContact'] ?? 'N/A'); ?></p>
                <p><strong>Fonction :</strong> <?php echo htmlspecialchars($gerant['fonctionContact'] ?? 'N/A'); ?></p>
                <p><strong>Actif :</strong> <?php echo isset($gerant['estActifContact']) ? ($gerant['estActifContact'] ? 'Oui' : 'Non') : 'N/A'; ?></p>
            </div>

            <form method="post">
                <div class="form-buttons">
                    <button type="button" onclick="window.location.href='new_stage_step6.php'">Retour</button>
                    <button type="submit">Valider le stage</button>
                </div>
            </form>
        </section>
    </main>
    <?php display_footer(); ?>
    <?php loading(); ?>
</body>


</html>