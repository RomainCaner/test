<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login_selection.php");
    exit;
}


// Si un idStage est passé en GET, on MODIFIE un stage existant
if (isset($_GET['idStage'])) {
    $_SESSION['idStage'] = (int) $_GET['idStage'];
    header("Location: new_stage_step6.php");
    exit;
} 
else if (isset($_SESSION['idStage'])) {
    unset($_SESSION['idStage']); // on nettoie la session idStage pour s'assurer d'être en mode création
}

// Redirection vers la première étape de création
header("Location: new_stage_step1.php");
exit;
