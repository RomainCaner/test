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

$siren = isset($_GET['siren']) ? $_GET['siren'] : '';
$nic = isset($_GET['nic']) ? $_GET['nic'] : '';

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

    header("Location: new_stage_step2.php?siret=" . $siren . $nic);
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

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'établissement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            text-align: center;
        }

        header .logo h1 {
            margin: 0;
        }

        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
        }

        nav ul li {
            margin: 0 10px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        nav ul li a:hover {
            text-decoration: underline;
        }

        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        section {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            width: 100%;
            margin: 20px auto;
            display: flex;
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
            box-sizing: border-box;
        }

        .form-buttons {
            text-align: right;
            margin-top: 20px;
        }

        button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #555;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #333;
        }

        footer {
            text-align: center;
            padding: 10px;
            background-color: #333;
            color: white;
            font-size: 12px;
            position: relative;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <h1>Portail des Stages</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <section>
            <h2>Modifier les informations de l'établissement</h2>
            <form action="edit_etablissement.php?siren=<?php echo $siren; ?>&nic=<?php echo $nic; ?>" method="post">
                <div class="form-group">
                    <label for="denomination">Dénomination :</label>
                    <input type="text" id="denomination" name="denomination" value="<?php echo htmlspecialchars($etablissement['denominationEtab']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="numAdr">Numéro d'adresse :</label>
                    <input type="text" id="numAdr" name="numAdr" value="<?php echo htmlspecialchars($etablissement['numAdrEtab']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="voie">Voie :</label>
                    <input type="text" id="voie" name="voie" value="<?php echo htmlspecialchars($etablissement['voieAdrEtab']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="libAdr">Libellé de l'adresse :</label>
                    <input type="text" id="libAdr" name="libAdr" value="<?php echo htmlspecialchars($etablissement['libAdrEtab']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="cp">Code Postal :</label>
                    <input type="text" id="cp" name="cp" value="<?php echo htmlspecialchars($etablissement['cpAdrEtab']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="ville">Ville :</label>
                    <input type="text" id="ville" name="ville" value="<?php echo htmlspecialchars($etablissement['villeAdrEtab']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="mission">Mission :</label>
                    <textarea id="mission" name="mission" rows="4" required><?php echo htmlspecialchars($etablissement['missionEtab']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="fixe">Téléphone :</label>
                    <input type="text" id="fixe" name="fixe" value="<?php echo htmlspecialchars($etablissement['fixeEtab']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="mail">Email :</label>
                    <input type="email" id="mail" name="mail" value="<?php echo htmlspecialchars($etablissement['mailEtab']); ?>" required>
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
    <footer>
        <p>© 2024 Institut Limayrac. Tous droits réservés.</p>
    </footer>
</body>
</html>

