<?php

function connexion() {
    $dsn = 'mysql:host=localhost;dbname=servwsbv_histostage_v2';
    try {
        $dbh = new PDO($dsn, 'servwsbv_cpuel', 'LeUpC&1Histo@', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbh;
    } catch (PDOException $ex) {
        die("Erreur lors de la connexion SQL : " . $ex->getMessage());
    }
}

function pre(array $tableau) {
    echo "<pre>";
    print_r($tableau);
    echo "</pre>";
}

function getStagesForUser($user_id) {
    $conn = connexion();
    $sql = "SELECT stage.idStage, stage.nomLieuStage, suivi.idStatut, statut.libStatut 
            FROM stage 
            JOIN suivi ON stage.idStage = suivi.idStage 
            JOIN statut ON suivi.idStatut = statut.idStatut 
            WHERE stage.idEtudiant = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
