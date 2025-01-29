<?php
session_start();
include 'functions.php'; // Inclure les fonctions nécessaires

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login_selection.php");
    exit;
}

// Vérifie si les informations de l'établissement sont disponibles dans la session
if (!isset($_SESSION['siret']) || !isset($_SESSION['etablissement'])) {
    header("Location: new_stage_step2.php");
    exit;
}

$siret = $_SESSION['siret'];
$etablissement = $_SESSION['etablissement'];

// Connexion à la base de données
$conn = connexion();

// Préparer les données pour l'insertion
$siren = substr($siret, 0, 9);
$nic = substr($siret, 9, 5);
$denomination = $etablissement['uniteLegale']['denominationUniteLegale'] ?? 'N/A';
$numAdr = $etablissement['adresseEtablissement']['numeroVoieEtablissement'] ?? 'N/A';
$typeVoie = $etablissement['adresseEtablissement']['typeVoieEtablissement'] ?? 'N/A';
$libAdr = $etablissement['adresseEtablissement']['libelleVoieEtablissement'] ?? 'N/A';
$codePostal = $etablissement['adresseEtablissement']['codePostalEtablissement'] ?? 'N/A';
$ville = $etablissement['adresseEtablissement']['libelleCommuneEtablissement'] ?? 'N/A';
$activite = $etablissement['uniteLegale']['activitePrincipaleUniteLegale'] ?? 'N/A';
$telephone = $etablissement['telephone'] ?? 'N/A';
$email = $etablissement['email'] ?? 'N/A';
$estSiege = $etablissement['etablissementSiege'] ? 1 : 0;

// Insérer les informations de l'établissement dans la base de données
$sql_insert = "INSERT INTO etablissement (SIREN, NIC, denominationEtab, numAdrEtab, typeVoieEtab, libAdrEtab, cpAdrEtab, villeAdrEtab, activitePrincipaleEtab, telephoneEtab, emailEtab, estSiegeSocialEtab) 
               VALUES (:siren, :nic, :denomination, :numAdr, :typeVoie, :libAdr, :codePostal, :ville, :activite, :telephone, :email, :estSiege)";
$stmt_insert = $conn->prepare($sql_insert);
$stmt_insert->bindParam(':siren', $siren);
$stmt_insert->bindParam(':nic', $nic);
$stmt_insert->bindParam(':denomination', $denomination);
$stmt_insert->bindParam(':numAdr', $numAdr);
$stmt_insert->bindParam(':typeVoie', $typeVoie);
$stmt_insert->bindParam(':libAdr', $libAdr);
$stmt_insert->bindParam(':codePostal', $codePostal);
$stmt_insert->bindParam(':ville', $ville);
$stmt_insert->bindParam(':activite', $activite);
$stmt_insert->bindParam(':telephone', $telephone);
$stmt_insert->bindParam(':email', $email);
$stmt_insert->bindParam(':estSiege', $estSiege);

if ($stmt_insert->execute()) {
    // Rediriger vers la page de l'étape suivante
    header("Location: new_stage_step3.php");
    exit();
} else {
    $error_message = "Une erreur s'est produite lors de l'enregistrement de l'établissement.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de l'établissement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .form-buttons {
            text-align: right;
            margin-top: 20px;
        }
        button {
            background-color: #555;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Confirmation de l'établissement</h2>
        <?php if (isset($error_message)): ?>
            <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <div class="form-buttons">
            <button type="button" onclick="window.location.href='new_stage_step2.php'">Retour</button>
        </div>
    </div>
</body>
</html>
