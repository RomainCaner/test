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

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $prenom = $_POST['prenom'];
    $nom = $_POST['nom'];
    $login = $_POST['login'];
    $email = $_POST['email'];
    $emailPerso = $_POST['emailPerso'];
    $titre = $_POST['titre'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $mobile = $_POST['mobile'];
    // met le fixe à null si vide
    $fixe = !empty($_POST['fixe']) ? $_POST['fixe'] : null;
    $statut = $_POST['statut'];
    $idClasse = $_POST['idClasse'];
    $idAnneeScolaire = $_POST['idAnneeScolaire'];
    $estRsEnseignant = $_POST['estRsEnseignant'];

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
                $role = 'P'; // Rôle Professeur
                $is_active = 1; // Utilisateur actif

                $sql_user = "INSERT INTO utilisateur (loginUser, mdpUser, titreUser, nomUser, prenomUser, mailUser, mailPersoUser, fixeUser, mobileUser, roleUser, estActifUser)
                VALUES (:login, :password, :titre, :nom, :prenom, :email, :emailPerso, :fixe, :mobile, :role, :is_active)";
                $stmt_user = $dbh->prepare($sql_user);
                $stmt_user->bindParam(':login', $login);
                $stmt_user->bindParam(':password', $hashed_password);
                $stmt_user->bindParam(':titre', $titre);
                $stmt_user->bindParam(':nom', $nom);
                $stmt_user->bindParam(':prenom', $prenom);
                $stmt_user->bindParam(':email', $email);
                $stmt_user->bindParam(':emailPerso', $emailPerso);
                $stmt_user->bindParam(':fixe', $fixe);
                $stmt_user->bindParam(':mobile', $mobile);
                $stmt_user->bindParam(':role', $role);
                $stmt_user->bindParam(':is_active', $is_active);

                // Exécution de l'insertion de l'utilisateur
                $stmt_user->execute();

                // Récupérer l'ID de l'utilisateur inséré
                $user_id = $dbh->lastInsertId();
                // Vérifier si l'ID utilisateur est valide
                if (!$user_id) {
                    $messages[] = "Erreur lors de l'insertion de l'utilisateur.";
                } else {
                    // Insérer les données de l'enseignant
                    $sql_enseignant = "INSERT INTO enseignant (idEnseignant, statutEnseignant) VALUES (:idEnseignant, :statutEnseignant)";
                    $stmt_enseignant = $dbh->prepare($sql_enseignant);
                    $stmt_enseignant->bindParam(':idEnseignant', $user_id);
                    $stmt_enseignant->bindParam(':statutEnseignant', $statut);

                    // Exécution de l'insertion de l'enseignant
                    $stmt_enseignant->execute();

                    if (!empty($idClasse) && is_array($idClasse)) {
                        // Parcourir chaque classe sélectionnée
                        foreach ($idClasse as $classe) {
                            // Préparer et exécuter l'insertion dans la table 'enseigner'
                            $sql_enseigner = "INSERT INTO enseigner (idEnseignant, idAnneeScolaire, idClasse, estRs) 
                                          VALUES (:idEnseignant, :idAnneeScolaire, :idClasse, :estRs)";
                            $stmt_enseigner = $dbh->prepare($sql_enseigner);
                            $stmt_enseigner->bindParam(':idEnseignant', $user_id);
                            $stmt_enseigner->bindParam(':idAnneeScolaire', $idAnneeScolaire);
                            $stmt_enseigner->bindParam(':idClasse', $classe);
                            $stmt_enseigner->bindParam(':estRs', $estRsEnseignant);

                            // Exécuter l'insertion
                            $stmt_enseigner->execute();
                        }
                    } else {
                        $messages[] = "Veuillez sélectionner au moins une classe.";
                    }
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
            // Annuler la transaction en cas d'erreur
            $dbh->rollBack();
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
    <title>Inscription Enseignant</title>
    <link rel="stylesheet" href="global_css.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>

<body>
    <?php display_header(); ?>
    <main>
        <section class="login-form">
            <h2>Inscription Enseignant</h2>
            <form action="register_enseignant.php" method="post">
                <div class="form-group">
                    <label for="prenom">Prénom :</label>
                    <input type="text" id="prenom" name="prenom" value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="nom">Nom :</label>
                    <input type="text" id="nom" name="nom" style="text-transform: uppercase;" value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="login">Login :</label>
                    <input type="text" id="login" name="login" value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email :</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="emailPerso">Email personnel :</label>
                    <input type="email" id="emailPerso" name="emailPerso" value="<?php echo isset($_POST['emailPerso']) ? htmlspecialchars($_POST['emailPerso']) : ''; ?>" required>
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
                    <input type="text" id="mobile" name="mobile" value="<?php echo isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="fixe">Téléphone fixe :</label>
                    <input type="text" id="fixe" name="fixe" value="<?php echo isset($_POST['fixe']) ? htmlspecialchars($_POST['fixe']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="statut">Statut :</label>
                    <input type="text" id="statut" name="statut" value="<?php echo isset($_POST['statut']) ? htmlspecialchars($_POST['statut']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="idClasse">Classes :</label>
                    <select id="idClasse" name="idClasse[]" class="form-select" multiple required>
                        <?php foreach ($classes as $classe): ?>
                            <option value="<?php echo $classe['idClasse']; ?>"
                                <?php echo (isset($_POST['idClasse']) && in_array($classe['idClasse'], $_POST['idClasse'])) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($classe['libClasse']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Maintenez Ctrl (ou Command) pour sélectionner plusieurs classes.</small>
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
                    <label for="estRsEnseignant">RS :</label>
                    <select id="estRsEnseignant" name="estRsEnseignant" required>
                        <option value="0" <?php echo (isset($_POST['estRsEnseignant']) && $_POST['estRsEnseignant'] == '0') ? 'selected' : ''; ?>>Non</option>
                        <option value="1" <?php echo (isset($_POST['estRsEnseignant']) && $_POST['estRsEnseignant'] == '1') ? 'selected' : ''; ?>>Oui</option>
                    </select>
                </div>

                <div class="form-group">
                    <!-- Bouton de Soumission Principal -->
                    <button class="form-buttons" type="submit">Inscrire</button>

                    <!-- Lien pour Retourner à la Sélection du Rôle -->
                    <a href="register_selection.php" class="form-buttons-link">
                        Retour
                    </a>
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