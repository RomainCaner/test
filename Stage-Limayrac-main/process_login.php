<?php
session_start();
include 'functions.php'; // Inclure les fonctions nécessaires

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Connexion à la base de données
    $dbh = connexion();

    // Préparer et exécuter la requête pour vérifier les informations de connexion
    $sql = "SELECT * FROM utilisateur WHERE mailUser = :email AND estActifUser = 1";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérifier si l'utilisateur existe et si le mot de passe est correct
    if ($user) {
        // Débogage : Afficher l'utilisateur récupéré
        // var_dump($user); // Décommentez cette ligne pour afficher les détails de l'utilisateur

        if (password_verify($password, $user['mdpUser'])) {
            // Authentification réussie, enregistrer les informations de l'utilisateur dans la session
            $_SESSION['user_id'] = $user['idUser'];
            $_SESSION['user_name'] = $user['prenomUser'] . ' ' . $user['nomUser'];
            $_SESSION['user_email'] = $user['mailUser'];
            $_SESSION['user_role'] = $user['roleUser'];

            // Rediriger vers la page d'accueil
            header("Location: index.php");
            exit();
        } else {
            // Débogage : Afficher un message si le mot de passe est incorrect
            echo "Mot de passe incorrect."; // Décommentez cette ligne pour afficher le message
            $error = "Adresse e-mail ou mot de passe incorrect.";
        }
    } else {
        // Débogage : Afficher un message si l'utilisateur n'existe pas
        echo "Utilisateur non trouvé ou inactif."; // Décommentez cette ligne pour afficher le message
        $error = "Adresse e-mail ou mot de passe incorrect.";
    }
}
?>
