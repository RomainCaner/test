<?php
session_start();
include 'functions.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
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
                <h1 class="login-title">Choisissez le type d'utilisateur à inscrire</h1>
                <button onclick="window.location.href='register_enseignant.php'">Enseignant</button>
                <button onclick="window.location.href='register_eleve.php'">Élève</button>
            </div>
        </section>
    </main>
    <?php display_footer(); ?>
</body>
</html>