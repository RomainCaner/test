<?php
session_start();
session_destroy(); // Détruire toutes les données de la session
header("Location: login_selection.php"); // Rediriger vers la page de connexion
exit();
?>
