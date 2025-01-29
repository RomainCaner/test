<?php
session_start();
include 'functions.php'; // Inclure les fonctions nécessaires

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login_selection.php");
    exit;
}

// Connexion à la base de données
$conn = connexion();

$siren = isset($_GET['siren']) ? $_GET['siren'] : (isset($_SESSION['siren']) ? $_SESSION['siren'] : '');
$nic = isset($_GET['nic']) ? $_GET['nic'] : (isset($_SESSION['nic']) ? $_SESSION['nic'] : '');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $denomination = $_POST['denomination'];
    $numAdr = $_POST['numAdr'];
    $voie = $_POST['voie'];
    $libAdr = $_POST['libAdr'];
    $cp = $_POST['cp'];
    $ville = $_POST['ville'];
    $mission = $_POST['mission'];
    $fixe = $_POST['fixe'];
    $mail = $_POST['mail'];
    $estSiege = isset($_POST['estSiege']) ? 1 : 0;

    $sql_update = "UPDATE etablissement SET 
                    denominationEtab = :denomination, 
                    numAdrEtab = :numAdr, 
                    voieAdrEtab = :voie, 
                    libAdrEtab = :libAdr, 
                    cpAdrEtab = :cp, 
                    villeAdrEtab = :ville, 
                    missionEtab = :mission, 
                    fixeEtab = :fixe, 
                    mailEtab = :mail, 
                    estSiegeSocialEtab = :estSiege 
                   WHERE SIREN = :siren AND NIC = :nic";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bindParam(':denomination', $denomination);
    $stmt_update->bindParam(':numAdr', $numAdr);
    $stmt_update->bindParam(':voie', $voie);
    $stmt_update->bindParam(':libAdr', $libAdr);
    $stmt_update->bindParam(':cp', $cp);
    $stmt_update->bindParam(':ville', $ville);
    $stmt_update->bindParam(':mission', $mission);
    $stmt_update->bindParam(':fixe', $fixe);
    $stmt_update->bindParam(':mail', $mail);
    $stmt_update->bindParam(':estSiege', $estSiege, PDO::PARAM_INT);
    $stmt_update->bindParam(':siren', $siren);
    $stmt_update->bindParam(':nic', $nic);
    $stmt_update->execute();

    $siretComplet = $siren . $nic;
    header("Location: new_stage_step2.php?siret=" . $siretComplet);
    exit();
}

$sql = "SELECT * FROM etablissement WHERE SIREN = :siren AND NIC = :nic";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':siren', $siren);
$stmt->bindParam(':nic', $nic);
$stmt->execute();
$etablissement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$etablissement) {
    echo "Établissement non trouvé.";
    exit;
}

// Fonction pour éviter les valeurs nulles dans htmlspecialchars
function safe_htmlspecialchars($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'établissement</title>
    <link rel="stylesheet" href="global_css.css"> <!-- Remplacé main.css par global_css.css -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>

<body>
<?php display_header();?>

    <main>
        <section class="form-content"> <!-- Ajout de la classe form-content -->
            <h2>Modifier les informations de l'établissement</h2>
            <form action="modifier_etablissement.php?siren=<?php echo safe_htmlspecialchars($siren); ?>&nic=<?php echo safe_htmlspecialchars($nic); ?>" method="post">
                <div class="form-group">
                    <label for="denomination">Dénomination :</label>
                    <input type="text" id="denomination" name="denomination" value="<?php echo safe_htmlspecialchars($etablissement['denominationEtab']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="numAdr">Numéro d'adresse :</label>
                    <input type="text" id="numAdr" name="numAdr" value="<?php echo safe_htmlspecialchars($etablissement['numAdrEtab']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="voie">Voie :</label>
                    <input type="text" id="voie" name="voie" value="<?php echo safe_htmlspecialchars($etablissement['voieAdrEtab']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="libAdr">Libellé de l'adresse :</label>
                    <input type="text" id="libAdr" name="libAdr" value="<?php echo safe_htmlspecialchars($etablissement['libAdrEtab']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="cp">Code Postal :</label>
                    <input type="text" id="cp" name="cp" value="<?php echo safe_htmlspecialchars($etablissement['cpAdrEtab']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="ville">Ville :</label>
                    <input type="text" id="ville" name="ville" value="<?php echo safe_htmlspecialchars($etablissement['villeAdrEtab']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="mission">Mission :</label>
                    <textarea id="mission" name="mission" rows="4" required><?php echo safe_htmlspecialchars($etablissement['missionEtab']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="fixe">Téléphone :</label>
                    <input type="text" id="fixe" name="fixe" value="<?php echo safe_htmlspecialchars($etablissement['fixeEtab']); ?>" >
                </div>
                <div class="form-group">
                    <label for="mail">Email :</label>
                    <input type="email" id="mail" name="mail" value="<?php echo safe_htmlspecialchars($etablissement['mailEtab']); ?>" >
                </div>
                <div class="form-group">
                    <label for="estSiege">Est Siège Social :</label>
                    <input type="checkbox" id="estSiege" name="estSiege" <?php echo $etablissement['estSiegeSocialEtab'] ? 'checked' : ''; ?>>
                </div>
                <div class="form-buttons">
                    <button type="submit">Enregistrer</button>
                    <button type="button" onclick="window.location.href='new_stage_step2.php'">Annuler</button>
                </div>
            </form>
        </section>
    </main>
    <?php display_footer(); ?>
    <?php loading(); ?>
</body>

</html>
