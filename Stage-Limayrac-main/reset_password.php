<?php
session_start();
include 'functions.php'; // Inclure la fonction `connexion`

$messages = [];
$successMessage = "";

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation des champs
    if (empty($login)) {
        $messages[] = "Le login est obligatoire.";
    }
    if (empty($password) || empty($confirm_password)) {
        $messages[] = "Les champs de mot de passe sont obligatoires.";
    } elseif ($password !== $confirm_password) {
        $messages[] = "Les mots de passe ne correspondent pas.";
    } elseif (mb_strlen($password) < 8 || !preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W]/', $password)) {
        $messages[] = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
    }

    // Si aucune erreur, mise à jour du mot de passe
    if (empty($messages)) {
        try {
            $dbh = connexion();

            // Vérifier si le login existe
            $sql_check = "SELECT idUser FROM utilisateur WHERE loginUser = :login OR mailUser = :login";
            $stmt_check = $dbh->prepare($sql_check);
            $stmt_check->bindParam(':login', $login, PDO::PARAM_STR);
            $stmt_check->execute();
            $user = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $messages[] = "Aucun utilisateur trouvé avec ce login ou email.";
            } else {
                // Mettre à jour le mot de passe
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql_update = "UPDATE utilisateur SET mdpUser = :password WHERE idUser = :idUser";
                $stmt_update = $dbh->prepare($sql_update);
                $stmt_update->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                $stmt_update->bindParam(':idUser', $user['idUser'], PDO::PARAM_INT);
                $stmt_update->execute();

                $successMessage = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez vous connecter.";
                header("Location: login_selection.php");
            }
        } catch (Exception $e) {
            $messages[] = "Une erreur est survenue : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modification du mot de passe</title>
    <link rel="stylesheet" href="global_css.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
<?php display_header(); ?>
<main>
    <section class="login-form">
        <h2>Réinitialisation du mot de passe</h2>
        <?php if (!empty($messages)): ?>
            <div class="error-messages">
                <ul>
                    <?php foreach ($messages as $msg): ?>
                        <li><?php echo htmlspecialchars($msg); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="success-message">
                <p><?php echo htmlspecialchars($successMessage); ?></p>
            </div>
        <?php else: ?>
            <form action="reset_password.php" method="post">
                <div class="form-group">
                    <label for="login">Login ou Email :</label>
                    <input type="text" id="login" name="login" required>
                </div>
                <div class="form-group">
                    <label for="password">Nouveau mot de passe :</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe :</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <!-- Bouton de Soumission Principal -->
                    <button class="form-buttons" type="submit">Réinitialiser</button>

                    <!-- Lien pour Retourner à la Sélection du Rôle -->
                    <a href="login_selection.php" class="form-buttons-link">
                        Retour
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>
<?php display_footer(); ?>
</body>
</html>
