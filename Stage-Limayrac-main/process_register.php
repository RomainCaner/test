<?php

// Inclure le fichier de fonctions
include('functions.php');

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérifier si les champs sont vides
    if (empty($_POST['email']) || empty($_POST['password'])) {
        $error = "Veuillez remplir tous les champs.";
        include("register.php");
        exit();
    } else {
        // Récupérer les données du formulaire
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Connexion à la base de données
        $conn = connexion();

        // Hasher le mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Préparer la requête d'insertion
        $sql = "INSERT INTO utilisateur (mailUser, mdpUser) VALUES (:email, :password)";

        // Exécuter la requête avec les paramètres
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);

        // Exécuter la requête
        if ($stmt->execute()) {
            // Rediriger vers une page de confirmation ou de connexion
            header("Location: login.php");
            exit();
        } else {
            $error = "Une erreur s'est produite lors de l'inscription.";
            include("register.php");
            exit();
        }
    }
}

?>
