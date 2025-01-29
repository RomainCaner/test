<?php
session_start();
include 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login_selection.php");
    exit;
}

// Désactiver l'affichage des erreurs pour éviter les sorties non intentionnelles
ini_set('display_errors', 0);
error_reporting(0);

// Inclure le fichier autoload de Composer
require 'vendor/autoload.php';

// Utiliser la classe TCPDF
use TCPDF;

// Connexion à la base de données
$conn = connexion();

// Vérifier si l'utilisateur est un enseignant et a sélectionné un élève
if ($_SESSION['user_role'] == 'P' && isset($_SESSION['selected_user']) || $_SESSION['user_role'] == 'A' && isset($_SESSION['selected_user'])) {
    $user_id = $_SESSION['selected_user'];
} else {
    // Sinon, utiliser l'ID de l'utilisateur connecté (cas de l'élève)
    $user_id = $_SESSION['user_id'];
}

// Récupérer les valeurs envoyées par le formulaire
$idStage = isset($_POST['idStage']) ? $_POST['idStage'] : null;
$gratification = isset($_POST['gratification']) ? $_POST['gratification'] : null;
$action = isset($_POST['action']) ? $_POST['action'] : null;

// Vérifier si l'action est 'update_and_download'
if ($action == 'update_and_download' && $idStage && $gratification) {
    // Mise à jour de la table 'stage' avec la gratification
    $sql_update_gratification = "UPDATE stage SET gratification = :gratification WHERE idStage = :idStage";
    $stmt_update = $conn->prepare($sql_update_gratification);
    $stmt_update->bindParam(':gratification', $gratification, PDO::PARAM_STR);
    $stmt_update->bindParam(':idStage', $idStage, PDO::PARAM_INT);
    $stmt_update->execute();
    
    // Vérifier si la mise à jour a réussi
    if ($stmt_update->rowCount() > 0) {
        // Mise à jour réussie, générer le PDF avec le montant mis à jour
        generatePDF($idStage, $gratification, $conn);  // Une fonction que vous pouvez définir pour générer le PDF avec la gratification mise à jour
    } else {
        echo "Erreur lors de la mise à jour de la gratification.";
    }
} else {
    echo "Données invalides ou action non autorisée.";
}

function generatePDF($idStage, $gratification, $conn) {
    // Votre logique de génération de PDF ici
    // Vous pouvez maintenant inclure le montant de la gratification dans le PDF
    $sql_stage = "SELECT * FROM stage WHERE idStage = :idStage";
    $stmt_stage = $conn->prepare($sql_stage);
    $stmt_stage->bindParam(':idStage', $idStage, PDO::PARAM_INT);
    $stmt_stage->execute();
    $stage_info = $stmt_stage->fetch(PDO::FETCH_ASSOC);

    if (!$stage_info) {
        die('Informations sur le stage introuvables.');
    }
}

// Récupérer les informations de l'utilisateur (élève)
$sql_user = "SELECT * FROM utilisateur 
             JOIN etudiant ON utilisateur.idUser = etudiant.idEtudiant
             JOIN inscription ON etudiant.idEtudiant = inscription.idEtudiant 
             JOIN classe ON inscription.idClasse = classe.idClasse
             JOIN stage ON anneescolaire.idAnneeScolaire = stage.idAnneeScolaire
             WHERE utilisateur.idUser = :user_id";
             
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_user->execute();
$user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

// Récupérer les informations du stage le plus récent
$sql_stage = "SELECT * FROM stage 
              JOIN etablissement ON stage.SIREN = etablissement.SIREN AND stage.NIC = etablissement.NIC
              WHERE stage.idEtudiant = :user_id 
              ORDER BY stage.idStage DESC LIMIT 1";
$stmt_stage = $conn->prepare($sql_stage);
$stmt_stage->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_stage->execute();
$stage_info = $stmt_stage->fetch(PDO::FETCH_ASSOC);

if (!$stage_info) {
    die('Informations sur le stage introuvables.');
}

// Récupérer les informations du tuteur
$tuteur_id = $stage_info['idContact'];
$sql_tuteur = "SELECT * FROM contact WHERE idContact = :tuteur_id";
$stmt_tuteur = $conn->prepare($sql_tuteur);
$stmt_tuteur->bindParam(':tuteur_id', $tuteur_id, PDO::PARAM_INT);
$stmt_tuteur->execute();
$tuteur_info = $stmt_tuteur->fetch(PDO::FETCH_ASSOC);

// Création du PDF
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetMargins(15, 15, 15);

// Dessiner un rectangle pour le logo de l'entreprise
$pdf->SetLineWidth(0.3); // Définir l'épaisseur de la bordure
$pdf->Rect(16, 13, 30, 13); // Dessiner un rectangle (x, y, largeur, hauteur)

// Titre de l'attestation
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 20, 'ATTESTATION DE STAGE', 0, 1, 'R');

// Informations de l'organisme d'accueil
$pdf->SetFont('helvetica', '', 10);

// Créer un cadre autour de la section
$pdf->SetXY(15, $pdf->GetY()); // Commencer à partir de la position courante
$pdf->SetLineWidth(0.3);
$pdf->Rect(15, $pdf->GetY(), 180, 31); // Dessiner un rectangle autour de la section (ajuster les dimensions)

// Ajouter les détails de l'organisme d'accueil à l'intérieur du cadre
$pdf->SetXY(15, $pdf->GetY() + 2);
$pdf->SetFont('helvetica', 'BU', 10);
$pdf->Cell(0, 7, 'ORGANISME D\'ACCUEIL', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 7, 'Nom ou dénomination sociale: ' . $stage_info['denominationEtab'], 0, 1, 'L');
$pdf->Cell(0, 7, 'Adresse: ' . $stage_info['numAdrEtab'] . ' ' . $stage_info['voieAdrEtab'] . ', ' . $stage_info['cpAdrEtab'] . ' ' . $stage_info['villeAdrEtab'], 0, 1, 'L');
$pdf->Cell(0, 7, 'Téléphone: ' . $stage_info['fixeEtab'], 0, 1, 'L');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 10, 'certifie que', 0, 1, 'L');

$pdf->SetXY(15, $pdf->GetY());
$pdf->SetLineWidth(0.3);
$pdf->Rect(15, $pdf->GetY(), 180, 73); 

$pdf->SetXY(15, $pdf->GetY() + 2);

// Informations sur le stagiaire
$pdf->SetFont('helvetica', 'BU', 10); 
$pdf->Cell(0, 7, 'LA OU LE STAGIAIRE', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 7, 'Sexe: ' . $user_info['titreUser'], 0, 1, 'L');
$pdf->Cell(0, 7, 'Nom: ' . $user_info['nomUser'], 0, 1, 'L');
$pdf->Cell(0, 7, 'Prénom: ' . $user_info['prenomUser'], 0, 1, 'L');
$pdf->Cell(0, 7, 'Né(e) le: ' . date('d/m/Y', strtotime($user_info['dateNaissanceEtudiant'])), 0, 1, 'L');
$pdf->Cell(0, 7, 'Adresse: ' . $user_info['numAdrEtudiant'] . ' ' . $user_info['voieAdrEtudiant'] . ', ' . $user_info['cpAdrEtudiant'] . ' ' . $user_info['villeAdrEtudiant'], 0, 1, 'L');
$pdf->Cell(0, 7, 'Téléphone: ' . $user_info['mobileUser'], 0, 1, 'L');
$pdf->Cell(0, 7, 'Email: ' . $user_info['mailUser'], 0, 1, 'L');
$pdf->Cell(0, 7, 'Classe: ' . $user_info['libClasse'], 0, 1, 'L');
$pdf->Cell(0, 7, 'Nom de l\'établissement d\'enseignement: ASSOCIATION INSTITUT LIMAYRAC  ', 0, 1, 'L');

// ecrit certifie que en gras
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 10, 'a effectué un stage prévu dans le cadre de ses études', 0, 1, 'L');

$pdf->SetXY(15, $pdf->GetY());
$pdf->SetLineWidth(0.3);
$pdf->Rect(15, $pdf->GetY(), 180, 62); 

$pdf->SetXY(15, $pdf->GetY() + 2);
// Informations sur le stage
$pdf->SetFont('helvetica', 'BU', 10);  
$pdf->Cell(0, 7, 'DURÉE DU STAGE', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);  
$pdf->Cell(0, 7, 'Dates de début et de fin du stage: du ' . date('d/m/Y', strtotime($stage_info['dateDebutStage'])) . ' au ' . date('d/m/Y', strtotime($stage_info['dateFinStage'])), 0, 1, 'L');
// Calcul de la durée totale en jours
$total_days = (strtotime($stage_info['dateFinStage']) - strtotime($stage_info['dateDebutStage'])) / (60 * 60 * 24);
// Calcul du nombre de semaines et de jours restants
$weeks = floor($total_days / 7);  // Nombre de semaines entières
$remaining_days = $total_days % 7;  // Nombre de jours restants
// Affichage de la durée totale en semaines et jours
$pdf->Cell(0, 7, 'Durée totale: ' . $weeks . ' semaines et ' . $remaining_days . ' jours', 0, 1, 'L');

// Texte sur la durée totale du stage, en petit et sur toute la largeur
$pdf->SetXY(15, 180); // Positionner le texte à une position Y spécifique (ajustez selon vos besoins)
$pdf->SetFont('helvetica', '', 8); // Définir une petite taille de police
$pdf->MultiCell(
    180, // Largeur pour occuper presque toute la largeur
    5,   // Hauteur de chaque ligne
    "La durée totale du stage est appréciée en tenant compte de la présence effective de la ou du stagiaire dans l’organisme, sous réserve des droits à congés et autorisations d’absence prévus à l’article L.124-13 du code de l’éducation (art. L.124-18 du code de l’éducation). Chaque période au moins égale à 7 heures de présence consécutives ou non est considérée comme équivalente à un jour de stage et chaque période au moins égale à 22 jours de présence consécutifs ou non est considérée comme équivalente à un mois.",
    0,   // Pas de bordure
    'J', // Justifier le texte (gauche et droite)
    false // Pas de fond coloré
);


$pdf->SetXY(15, $pdf->GetY());
$pdf->SetLineWidth(0.3);
$pdf->Rect(15, $pdf->GetY(), 180, 20); 

// Gratification
$pdf->SetFont('helvetica', 'BU', 10);
$pdf->Cell(0, 10, 'MONTANT DE LA GRATIFICATION VERSÉE À LA OU AU STAGIAIRE', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
// Ajoutez vos autres sections du PDF ici
$pdf->Cell(0, 7, 'Montant de la gratification : ' . $gratification . ' €', 0, 1, 'L');


// Signature
$pdf->Ln(0);
$pdf->SetXY(100, $pdf->GetY() + 15); // Positionner à droite
$pdf->MultiCell(
    80, // Largeur pour ajuster la zone d'affichage
    5,  // Hauteur de chaque ligne
    "Nom, fonction et signature de la personne représentant de l'organisme d'accueil", 
    0,  // Pas de bordure
    'R', // Alignement à droite
    false // Pas de fond coloré
);

// Texte explicatif à gauche de la signature
$pdf->SetXY(15, 216); // Positionner le texte légèrement en dessous de la position actuelle
$pdf->SetFont('helvetica', '', 8); // Définir une petite taille de police
$pdf->MultiCell(
    80, // Largeur pour limiter le texte sur la gauche
    5,  // Hauteur de chaque ligne
    "L’attestation de stage est indispensable pour pouvoir, sous réserve du versement d’une cotisation, faire prendre en compte le stage dans les droits à retraite. La législation sur les retraites (loi n°2014-40 du 20 janvier 2014) ouvre aux étudiants dont le stage a été gratifié la possibilité de faire valider celui-ci dans la limite de deux trimestres, sous réserve du versement d’une cotisation. La demande est à faire par l’étudiant(e) dans les deux années suivant la fin du stage et sur présentation obligatoire de l’attestation de stage mentionnant la durée totale du stage et le montant total de la gratification perçue. Les informations précises sur la cotisation à verser et sur la procédure à suivre sont à demander auprès de la Sécurité sociale (code de la Sécurité sociale art. L.351-17 – code de l’éducation art. D.124-9).",
    0,  // Pas de bordure
    'L', // Alignement à gauche
    false // Pas de fond coloré
);

// Générer le fichier PDF
ob_end_clean();
$pdf->Output('attestation_de_stage.pdf', 'I');
?>
