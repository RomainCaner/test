<?php
session_start();
include 'functions.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login_selection.php");
    exit;
}

require 'vendor/autoload.php';
use TCPDF;

//--------------------------------------------
// (1) Connexion à la base
//--------------------------------------------
$conn = connexion();

//--------------------------------------------
// (2) Récupération de l'idStage passé en GET
//--------------------------------------------
$stage_id = isset($_GET['idStage']) ? intval($_GET['idStage']) : null;
if (!$stage_id) {
    die('Aucun identifiant de stage fourni.');
}

//--------------------------------------------
// (3) Récupération des infos du stage
//--------------------------------------------
$sql_stage = "
    SELECT 
        s.*,
        e.denominationEtab,
        e.numAdrEtab,
        e.voieAdrEtab,
        e.libAdrEtab,
        e.cpAdrEtab,
        e.villeAdrEtab,
        e.missionEtab,
        e.fixeEtab,
        e.mailEtab
    FROM stage s
    JOIN etablissement e 
      ON s.SIREN = e.SIREN
     AND s.NIC   = e.NIC
    WHERE s.idStage = :stage_id
";
$stmt_stage = $conn->prepare($sql_stage);
$stmt_stage->bindParam(':stage_id', $stage_id, PDO::PARAM_INT);
$stmt_stage->execute();
$stage_info = $stmt_stage->fetch(PDO::FETCH_ASSOC);

if (!$stage_info) {
    die('Informations sur le stage introuvables.');
}

// Identifiants clés
$etudiant_id       = $stage_info['idEtudiant'];
$classe_id         = $stage_info['idClasse'];         
$anneeScolaire_id  = $stage_info['idAnneeScolaire'];  

//--------------------------------------------
// (4) Déterminer l'utilisateur (élève ou prof/admin)
//--------------------------------------------
if (
    (($_SESSION['user_role'] == 'P' || $_SESSION['user_role'] == 'A') 
     && isset($_SESSION['selected_user']))
) {
    $user_id = $_SESSION['selected_user'];
} else {
    $user_id = $_SESSION['user_id'];
}

//--------------------------------------------
// (5) Récupération des infos de l'étudiant
//--------------------------------------------
$sql_user = "
    SELECT 
        utilisateur.*,
        etudiant.*,
        inscription.*,
        classe.*,
        anneeScolaire.*
    FROM utilisateur
    JOIN etudiant 
        ON utilisateur.idUser = etudiant.idEtudiant
    JOIN inscription 
        ON etudiant.idEtudiant = inscription.idEtudiant
       AND inscription.idClasse = :classe_id
       AND inscription.idAnneeScolaire = :annee_id
       AND inscription.idEtudiant = :etudiant_id
    JOIN classe 
        ON inscription.idClasse = classe.idClasse
    JOIN anneeScolaire
        ON inscription.idAnneeScolaire = anneeScolaire.idAnneeScolaire
    WHERE utilisateur.idUser = :etudiant_id
";

$stmt_user = $conn->prepare($sql_user);
$stmt_user->bindParam(':classe_id',    $classe_id,         PDO::PARAM_STR);
$stmt_user->bindParam(':annee_id',    $anneeScolaire_id,  PDO::PARAM_INT);
$stmt_user->bindParam(':etudiant_id', $etudiant_id,       PDO::PARAM_INT);
$stmt_user->execute();

$user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);
if (!$user_info) {
    die('Informations de l’étudiant introuvables pour cette année scolaire.');
}

//--------------------------------------------
// (6) Récupération du responsable signataire
//     et du maître de stage
//--------------------------------------------
$signataire_id = $stage_info['idSignataire'] ?? null;
$sql_signataire = "SELECT * FROM contact WHERE idContact = :idSignataire";
$stmt_signataire = $conn->prepare($sql_signataire);
$stmt_signataire->bindParam(':idSignataire', $signataire_id, PDO::PARAM_INT);
$stmt_signataire->execute();
$signataire_info = $stmt_signataire->fetch(PDO::FETCH_ASSOC);

// Maître de stage (not NULL => forcément présent)
$maitre_id = $stage_info['idMaitreDeStage'];
$sql_maitre = "SELECT * FROM contact WHERE idContact = :maitre_id";
$stmt_maitre = $conn->prepare($sql_maitre);
$stmt_maitre->bindParam(':maitre_id', $maitre_id, PDO::PARAM_INT);
$stmt_maitre->execute();
$maitre_info = $stmt_maitre->fetch(PDO::FETCH_ASSOC);

if (!$maitre_info) {
    die('Aucun maître de stage trouvé.');
}

//--------------------------------------------
// (7) Récupération du Professeur référent
//--------------------------------------------
$enseignant_id = $stage_info['idEnseignant'];
$prof_referent = 'Non assigné';

if (!empty($enseignant_id)) {
    $sql_prof = "
        SELECT u.nomUser, u.prenomUser
        FROM enseignant e
        JOIN utilisateur u 
          ON e.idEnseignant = u.idUser
        WHERE e.idEnseignant = :idEns
    ";
    $stmt_prof = $conn->prepare($sql_prof);
    $stmt_prof->bindParam(':idEns', $enseignant_id, PDO::PARAM_INT);
    $stmt_prof->execute();
    $prof_info = $stmt_prof->fetch(PDO::FETCH_ASSOC);

    if ($prof_info) {
        $prof_referent = trim($prof_info['prenomUser'] . ' ' . $prof_info['nomUser']);
    }
}

//--------------------------------------------
// (8) Configuration TCPDF
//--------------------------------------------
$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(true, 15);
$pdf->SetMargins(15, 10, 15);
$pdf->setCellPaddings(2, 2, 2, 2);

// Nouvelle page
$pdf->AddPage();

//--------------------------------------------
// (9) En-tête 
//--------------------------------------------
// Logo
$pdf->Image('img/limayraclogo.jpg', 14, 5, 31, 10);

// Titre principal (centré)
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(16, 5);
$pdf->Cell(0, 10, 'Fiche de renseignements', 0, 1, 'C');

// Année scolaire (à droite)
$pdf->SetFont('helvetica', 'B', 14);

$pdf->SetXY(152, 5);
$pdf->Cell(
    45,        // largeur de la cellule
    10,        // hauteur
    $user_info['libAnneeScolaire'], 
    0,         // bordure
    0,         // saut de ligne après ?
    'R'        // alignement à droite
);

$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.3);
$pdf->Line(15, 17, $pdf->getPageWidth() - 15, 17);
$pdf->SetY(20);

//--------------------------------------------
// (10) Fonction utilitaire pour créer un bloc
//--------------------------------------------
function add_block($pdf, $title, $data, $smallTitle = false)
{
    $headerFillColor = [201, 232, 255]; 
    $borderColor     = [100, 100, 100];

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, $title, 0, 1, 'C', false);
    $pdf->Ln(0);

    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetFillColor(...$headerFillColor);
    $pdf->SetDrawColor(...$borderColor);
    $pdf->SetLineWidth(0.2);

    $lineHeight    = 6; 
    $firstColWidth = 60;

    foreach ($data as $key => $value) {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->MultiCell(
            $firstColWidth,
            $lineHeight,
            $key . ' :',
            1,
            'L',
            true,
            0,
            '',
            '',
            true,
            0,
            false,
            true,
            $lineHeight,
            'M'
        );

        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(
            0,
            $lineHeight,
            $value,
            1,
            'L',
            false,
            1,
            '',
            '',
            true,
            0,
            false,
            true,
            $lineHeight,
            'M'
        );
    }

    $pdf->Ln(2);
}

//--------------------------------------------
// (11) Blocs de données
//--------------------------------------------

// Adresse durant le stage
if (!empty($stage_info['adrEtudiantStage'])) {
    $adresse_stage = $stage_info['adrEtudiantStage'];
} else {
    $adresse_stage = 
        $user_info['numAdrEtudiant'] . ' ' .
        $user_info['voieAdrEtudiant'] . ' ' .
        $user_info['libAdrEtudiant'];
    
    if (!empty($user_info['lib2AdrEtudiant'])) {
        $adresse_stage .= ' ' . $user_info['lib2AdrEtudiant'];
    }

    $adresse_stage .= ', ' . $user_info['cpAdrEtudiant'] . ' ' . $user_info['villeAdrEtudiant'];
}

// (a) STAGE
$stage_data = [
    'Professeur référent'          => $prof_referent, 
    'Date'                         => 'du ' . $stage_info['dateDebutStage'] . ' au ' . $stage_info['dateFinStage'],
    'Durée de travail hebdomadaire' => $stage_info['dureeHebdoStage'] . ' heures',
    'Activités pendant le stage'    => $stage_info['activitesStage']
];
add_block($pdf, 'STAGE', $stage_data);

// (b) ÉTUDIANT
$etudiant_data = [
    'Nom et Prénom'         => $user_info['prenomUser'] . ' ' . $user_info['nomUser'],
    'Classe'                => $user_info['libClasse'],
    'Mail'                  => $user_info['mailUser'],
    'Téléphone'             => $user_info['mobileUser'] . ' / ' . $user_info['fixeUser'],
    'Adresse durant le stage' => $adresse_stage
];
add_block($pdf, 'ÉTUDIANT', $etudiant_data);

// (c) ENTREPRISE SIGNATAIRE
$entreprise_data = [
    'Nom de l\'organisme signataire' => $stage_info['denominationEtab'],
    'Adresse' => 
        $stage_info['numAdrEtab'] . ' ' .
        $stage_info['voieAdrEtab'] . ' ' .
        $stage_info['libAdrEtab'] . ', ' .
        $stage_info['cpAdrEtab'] . ' ' .
        $stage_info['villeAdrEtab'],
    'Téléphone' => $stage_info['fixeEtab'],
    'N°SIRET'   => $stage_info['SIREN'] . $stage_info['NIC'],
    'Missions de l\'organisme' => $stage_info['missionEtab']
];
add_block($pdf, 'ENTREPRISE / ORGANISME SIGNATAIRE DE LA CONVENTION', $entreprise_data);

// (d) RESPONSABLE SIGNATAIRE
if ($signataire_info) {
    $responsable_data = [
        'Nom du responsable signataire' => $signataire_info['nomContact'] . ' ' . $signataire_info['prenomContact'],
        'Fonction' => $signataire_info['fonctionContact'],
        'Mail'     => $signataire_info['mailContact'],
        'Téléphone' => $signataire_info['mobileContact'] . ' / ' . $signataire_info['fixeContact']
    ];
    add_block($pdf, 'RESPONSABLE SIGNATAIRE', $responsable_data);
}

// (e) MAÎTRE DE STAGE
$maitre_data = [
    'Nom et Prénom du maître de stage' => $maitre_info['nomContact'] . ' ' . $maitre_info['prenomContact'],
    'Fonction' => $maitre_info['fonctionContact'],
    'Mail'     => $maitre_info['mailContact'],
    'Téléphone' => $maitre_info['mobileContact'] . ' / ' . $maitre_info['fixeContact']
];
add_block($pdf, 'MAÎTRE DE STAGE', $maitre_data);

// (f) LIEU DE STAGE DIFFÉRENT ?
if (!empty($stage_info['nomLieuStage']) || !empty($stage_info['adrLieuStage'])) {
    $lieu_stage_data = [
        'Organisme où le stage se déroulera' => $stage_info['nomLieuStage'],
        'Téléphone'                          => $stage_info['contactLieuStage'],
        'Adresse'                            => $stage_info['adrLieuStage']
    ];
    add_block($pdf, 'SI LIEU DU STAGE EST DIFFÉRENT DU SITE DE L\'ENTREPRISE', $lieu_stage_data, true);
}

//--------------------------------------------
// (12) Sortie du PDF
//--------------------------------------------
ob_end_clean();
$pdf->Output('fiche_renseignements.pdf', 'I');
