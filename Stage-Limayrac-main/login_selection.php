<?php
session_start();
include 'functions.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="global_css.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>

<body>
    <?php display_header(); ?>
    <main>
        <section class="login-form">
            
            <div class="login-container">
                <h1 class="login-title">Choisissez votre espace</h1>
                <button onclick="window.location.href='login_enseignant.php'">Enseignant</button>
                <button onclick="window.location.href='login_eleve.php'">Élève</button>
                <button onclick="window.location.href='login_admin.php'">Administrateur</button>
            </div>
        </section>
    </main>
    <?php display_footer(); ?>
</body>

</html>