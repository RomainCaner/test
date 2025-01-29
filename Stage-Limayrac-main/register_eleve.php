<?php
session_start();
include 'functions.php'; // Inclure les fonctions nécessaires

// Initialisation des variables
$error = "";
$successMessage = "";
$messages = [];

// Connexion à la base de données
try {
    $dbh = connexion();

    // Récupérer les classes existantes
    $stmt_classes = $dbh->prepare("SELECT idClasse, libClasse FROM classe");
    $stmt_classes->execute();
    $classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer l'année scolaire active
    $stmt_annees = $dbh->prepare("SELECT idAnneeScolaire, libAnneeScolaire FROM anneescolaire WHERE estActiveAnneeScolaire = 1");
    $stmt_annees->execute();
    $annees = $stmt_annees->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $messages[] = "Erreur lors de la récupération des données : " . $e->getMessage();
}

// Si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $prenom = $_POST['prenom'];
    $nom = $_POST['nom'];
    $login = $_POST['login'];
    $email = $_POST['email'];
    $emailPerso = $_POST['emailPerso'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $mobile = $_POST['mobile'];
    // met le fixe à null si vide
    $fixe = !empty($_POST['fixe']) ? $_POST['fixe'] : null;
    $adresse_num = $_POST['adresse_num'];
    $adresse_voie = $_POST['adresse_voie'];
    $adresse_lib = $_POST['adresse_lib'];
    $code_postal = $_POST['code_postal'];
    $ville = $_POST['ville'];
    $date_naissance = $_POST['date_naissance'];
    $idClasse = $_POST['idClasse'];
    $idAnneeScolaire = $_POST['idAnneeScolaire'];
    $estRedoublant = $_POST['estRedoublant'];

    // Validation des champs
    if (empty($login) || empty($password)) {
        $messages[] = "Le login et le mot de passe sont obligatoires.";
    }

    if (mb_strlen($password) < 8) {
        $messages[] = "Le mot de passe doit faire au moins 8 caractères.";
    }

    if (!preg_match('/[a-z]/', $password)) {
        $messages[] = "Le mot de passe doit contenir au moins une lettre minuscule.";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $messages[] = "Le mot de passe doit contenir au moins une lettre majuscule.";
    }

    if (!preg_match('/[0-9]/', $password)) {
        $messages[] = "Le mot de passe doit contenir au moins un chiffre.";
    }

    if (!preg_match('/[\W]/', $password)) {
        $messages[] = "Le mot de passe doit contenir au moins un caractère spécial.";
    }

    if ($password !== $confirm_password) {
        $messages[] = "Les mots de passe ne correspondent pas.";
    }

    if (!ctype_digit($mobile) || mb_strlen($mobile) != 10) {
        $messages[] = "Le numéro de téléphone mobile doit contenir exactement 10 chiffres.";
    }

    if (!empty($fixe) && (!ctype_digit($fixe) || mb_strlen($fixe) != 10)) {
        $messages[] = "Le numéro de téléphone fixe doit contenir exactement 10 chiffres.";
    }

    if (!ctype_digit($code_postal) || mb_strlen($code_postal) != 5) {
        $messages[] = "Le code postal doit contenir exactement 5 chiffres.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $messages[] = "L'email professionnel n'est pas valide.";
    }
    
    if (!empty($emailPerso) && !filter_var($emailPerso, FILTER_VALIDATE_EMAIL)) {
        $messages[] = "L'email personnel n'est pas valide.";
    }
    

    if (count($messages) == 0) {
        try {
            // Connexion à la base de données
            $dbh = connexion();
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  // Affichage des erreurs SQL

            // Vérifier si le login, l'email ou l'email personnel existe déjà
$sql_check = "SELECT * FROM utilisateur WHERE loginUser = :login OR mailUser = :email OR mailPersoUser = :emailPerso";
$stmt_check = $dbh->prepare($sql_check);
$stmt_check->bindParam(':login', $login);
$stmt_check->bindParam(':email', $email);
$stmt_check->bindParam(':emailPerso', $emailPerso);
$stmt_check->execute();
$existing_user = $stmt_check->fetch(PDO::FETCH_ASSOC);

// Vérifier si un conflit existe
if ($existing_user) {
$messages[] = "Le login, l'email ou l'email personnel est déjà utilisé.";
            } else {
                // Commencer une transaction
                $dbh->beginTransaction();

                // Insérer l'utilisateur
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'E'; // Rôle par défaut : Étudiant
                $is_active = 1; // Utilisateur actif

                // Insérer l'utilisateur
$sql_user = "INSERT INTO utilisateur (loginUser, mdpUser, titreUser, nomUser, prenomUser, mailUser, mailPersoUser, fixeUser, mobileUser, roleUser, estActifUser)
VALUES (:login, :password, :titre, :nom, :prenom, :email, :emailPerso, :fixe, :mobile, :role, :is_active)";
$stmt_user = $dbh->prepare($sql_user);
$stmt_user->bindParam(':login', $login);
$stmt_user->bindParam(':password', $hashed_password);
$stmt_user->bindParam(':titre', $titre);
$stmt_user->bindParam(':role', $role);
$stmt_user->bindParam(':nom', $nom);
$stmt_user->bindParam(':prenom', $prenom);
$stmt_user->bindParam(':email', $email);
$stmt_user->bindParam(':emailPerso', $emailPerso);
$stmt_user->bindParam(':fixe', $fixe);
$stmt_user->bindParam(':mobile', $mobile);
$stmt_user->bindParam(':is_active', $is_active);

// Exécution de l'insertion de l'utilisateur
$stmt_user->execute();

// Récupérer l'ID de l'utilisateur inséré
$user_id = $dbh->lastInsertId();    

// Vérifier si l'ID utilisateur est valide
if (!$user_id) {
    $messages[] = "Erreur lors de l'insertion de l'utilisateur.";
} else {
    // Insérer les données de l'étudiant
    $sql_etudiant = "INSERT INTO etudiant (idEtudiant, numAdrEtudiant, voieAdrEtudiant, libAdrEtudiant, cpAdrEtudiant, villeAdrEtudiant,dateNaissanceEtudiant)
    VALUES (:idEtudiant, :adresse_num, :adresse_voie, :adresse_lib, :code_postal, :ville, :date_naissance)";
    $stmt_etudiant = $dbh->prepare($sql_etudiant);
    $stmt_etudiant->bindParam(':idEtudiant', $user_id);
    $stmt_etudiant->bindParam(':adresse_num', $adresse_num);
    $stmt_etudiant->bindParam(':adresse_voie', $adresse_voie);
    $stmt_etudiant->bindParam(':adresse_lib', $adresse_lib);
    $stmt_etudiant->bindParam(':code_postal', $code_postal);
    $stmt_etudiant->bindParam(':ville', $ville);
    $stmt_etudiant->bindParam(':date_naissance', $date_naissance);

    // Exécution de l'insertion de l'étudiant
    $stmt_etudiant->execute();

    // Insérer l'inscription
$sql_inscription = "INSERT INTO inscription (idClasse, idAnneeScolaire, idEtudiant, estRedoublant)
VALUES (:idClasse, :idAnneeScolaire, :idEtudiant, :estRedoublant)";
$stmt_inscription = $dbh->prepare($sql_inscription);
$stmt_inscription->bindParam(':idClasse', $idClasse);
$stmt_inscription->bindParam(':idAnneeScolaire', $idAnneeScolaire);
$stmt_inscription->bindParam(':idEtudiant', $user_id);
$stmt_inscription->bindParam(':estRedoublant', $estRedoublant);

// Exécution de l'insertion de l'inscription
$stmt_inscription->execute();

}
                // Commit de la transaction si tout est ok
                $dbh->commit();

                // Message de succès
                $successMessage = "Inscription réussie ! Vous pouvez vous connecter dès maintenant.";

                // Redirection après inscription
                header("Location: index_prof.php");
                exit;
            }
        } catch (Exception $e) {
            $dbh->rollBack(); // Annuler la transaction en cas d'erreur
            $messages[] = "Erreur lors de l'inscription : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Étudiant</title>
    <link rel="stylesheet" href="global_css.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>

<body>
    <?php display_header(); ?>
    <main>
        <section class="login-form">
            <h2>Inscription Étudiant</h2>
            <form action="register_eleve.php" method="post">
                <!-- Formulaire d'inscription -->
                <div class="form-group">
                    <label for="prenom">Prénom :</label>
                    <input type="text" id="prenom" name="prenom" value="<?php echo isset($prenom) ? htmlspecialchars($prenom) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="nom">Nom :</label>
                    <input type="text" id="nom" name="nom" style="text-transform: uppercase;" value="<?php echo isset($nom) ? htmlspecialchars($nom) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="login">Login :</label>
                    <input type="text" id="login" name="login" value="<?php echo isset($login) ? htmlspecialchars($login) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email :</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="emailPerso">Email personnel :</label>
                    <input type="email" id="emailPerso" name="emailPerso" value="<?php echo isset($emailPerso) ? htmlspecialchars($emailPerso) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe :</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe :</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="form-group">
    <label for="titre">Titre :</label>
    <select id="titre" name="titre" required>
        <option value="">Sélectionnez un titre</option>
        <option value="Mr" <?php echo (isset($_POST['titre']) && $_POST['titre'] == 'Mr') ? 'selected' : ''; ?>>Mr</option>
        <option value="Mme" <?php echo (isset($_POST['titre']) && $_POST['titre'] == 'Mme') ? 'selected' : ''; ?>>Mme</option>
    </select>
</div>
                <div class="form-group">
                    <label for="mobile">Téléphone mobile :</label>
                    <input type="text" id="mobile" name="mobile" value="<?php echo isset($mobile) ? htmlspecialchars($mobile) : NULL; ?>" required>
                </div>
                <div class="form-group">
                    <label for="fixe">Téléphone fixe :</label>
                    <input type="text" id="fixe" name="fixe" value="<?php echo isset($fixe) ? htmlspecialchars($fixe) : NULL; ?>">
                </div>
                <div class="form-group">
                    <label for="adresse_num">Numéro d'adresse :</label>
                    <input type="text" id="adresse_num" name="adresse_num" value="<?php echo isset($adresse_num) ? htmlspecialchars($adresse_num) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="adresse_voie">Voie :</label>
                    <input type="text" id="adresse_voie" name="adresse_voie" value="<?php echo isset($adresse_voie) ? htmlspecialchars($adresse_voie) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="adresse_lib">Complément d'adresse :</label>
                    <input type="text" id="adresse_lib" name="adresse_lib" value="<?php echo isset($adresse_lib) ? htmlspecialchars($adresse_lib) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="code_postal">Code postal :</label>
                    <input type="text" id="code_postal" name="code_postal" value="<?php echo isset($code_postal) ? htmlspecialchars($code_postal) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="ville">Ville :</label>
                    <input type="text" id="ville" name="ville" value="<?php echo isset($ville) ? htmlspecialchars($ville) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="date_naissance">Date de naissance :</label>
                    <input type="date" id="date_naissance" name="date_naissance" value="<?php echo isset($date_naissance) ? htmlspecialchars($date_naissance) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="idClasse">Classe :</label>
                    <select id="idClasse" name="idClasse" class="form-select" required>
                        <option value="">Sélectionnez une classe</option>
                        <?php foreach ($classes as $classe): ?>
                            <option value="<?php echo $classe['idClasse']; ?>"
                                <?php echo (isset($_POST['idClasse']) && $_POST['idClasse'] == $classe['idClasse']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($classe['libClasse']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
    <label for="idAnneeScolaire">Année scolaire active :</label>
    <?php if (!empty($annees)): ?>
        <input type="hidden" id="idAnneeScolaire" name="idAnneeScolaire" value="<?php echo $annees[0]['idAnneeScolaire']; ?>">
        <input type="text" value="<?php echo htmlspecialchars($annees[0]['libAnneeScolaire']); ?>" readonly>
    <?php else: ?>
        <p>Aucune année scolaire active trouvée.</p>
    <?php endif; ?>
</div>
                <div class="form-group">
    <label for="estRedoublant">Redoublant :</label>
    <select id="estRedoublant" name="estRedoublant" required>
        <option value="0" <?php echo (isset($_POST['estRedoublant']) && $_POST['estRedoublant'] == '0') ? 'selected' : ''; ?>>Non</option>
        <option value="1" <?php echo (isset($_POST['estRedoublant']) && $_POST['estRedoublant'] == '1') ? 'selected' : ''; ?>>Oui</option>
    </select>
</div>
                    <div class="form-group">
                    <button class="form-buttons" type="submit">Inscrire</button>
                    <a href="register_selection.php" class="form-buttons-link">Retour</a>
                </div>
            </form>

            <?php if (!empty($messages)): ?>
                <div class="error-messages">
                    <ul>
                        <?php foreach ($messages as $msg): ?>
                            <li><?php echo $msg; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($successMessage): ?>
                <div class="success-message">
                    <p><?php echo $successMessage; ?></p>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <?php display_footer(); ?>
</body>

</html>