<?php
session_start();
include 'functions.php'; // Inclure les fonctions nécessaires

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'];
    $password = $_POST['password'];

    // Connexion à la base de données
    $dbh = connexion();

    // Préparer et exécuter la requête pour vérifier les informations de connexion
    $sql = "SELECT * FROM utilisateur WHERE loginUser = :login AND roleUser = 'A' AND estActifUser = 1";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':login', $login);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérifier si l'utilisateur existe et si le mot de passe est correct
    if ($user && password_verify($password, $user['mdpUser'])) {
        // Authentification réussie, enregistrer les informations de l'utilisateur dans la session
        $_SESSION['user_id'] = $user['idUser'];
        $_SESSION['user_name'] = $user['prenomUser'] . ' ' . $user['nomUser'];
        $_SESSION['user_email'] = $user['mailUser'];
        $_SESSION['user_role'] = $user['roleUser'];

        // Rediriger vers la page d'accueil des administrateurs
        header("Location: index_prof.php");
        exit();
    } else {
        $error = "Login ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administrateur - Portail des Stages</title>
    <link rel="stylesheet" href="global_css.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>

<body>
    <?php display_header(); ?>
    <main>
        <section class="login-form">
            <h2>Connexion Administrateur</h2>
            <form action="login_admin.php" method="post">
                <div class="form-group">
                    <label for="login">Login :</label>
                    <input type="text" id="login" name="login" required>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe :</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <!-- Bouton de Soumission Principal -->
                    <button class="form-buttons" type="submit">Se connecter</button>

                    <!-- Lien pour Retourner à la Sélection du Rôle -->
                    <a href="login_selection.php" class="form-buttons-link">
                        Retour
                    </a>
                </div>
                <?php if (isset($error)) : ?>
                    <div class="error_login">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
            </form>
            <p><a class="download-link" href="reset_password.php">Mot de passe oublié ?</a></p>
        </section>
    </main>
    <?php display_footer(); ?>
    <?php loading(); ?>
</body>

</html>