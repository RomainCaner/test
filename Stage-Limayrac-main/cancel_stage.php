<?php
session_start();
include 'functions.php'; // Inclure les fonctions nécessaires

if (!isset($_SESSION['user_id'])) {
    header("Location: login_selection.php");
    exit;
}

if (!isset($_GET['idStage'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$idStage = $_GET['idStage'];

$conn = connexion();

try {
    // Commence une transaction
    $conn->beginTransaction();

    // Met à jour le statut du stage à "Stage annulé"
    $query = "UPDATE stage SET idStatut = 5 WHERE idStage = :idStage";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':idStage', $idStage, PDO::PARAM_INT);
    $stmt->execute();

    // Commit la transaction
    $conn->commit();

    header("Location: index.php");
    exit;
} catch (Exception $e) {
    // Rollback la transaction en cas d'erreur
    $conn->rollBack();
    echo "Erreur lors de l'annulation du stage : " . $e->getMessage();
}
?>
