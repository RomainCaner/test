<?php
session_start();
include 'functions.php'; // inclure vos fonctions (connexion DB, etc.)

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login_selection.php");
    exit;
}

// Désactiver l'affichage des erreurs
ini_set('display_errors', 0);
error_reporting(0);

// Inclure la librairie TCPDF via Composer
require 'vendor/autoload.php';
use TCPDF;

// -----------------------------------------------------------------------------
// Connexion / sélection user
// -----------------------------------------------------------------------------
$conn = connexion();

// Si user_role = P (prof) ou A (admin) et qu'un étudiant est sélectionné, alors
// on affiche la convention pour l'étudiant sélectionné. Sinon, c'est l'étudiant
// lui-même qui affiche sa convention.
if (($_SESSION['user_role'] == 'P' && isset($_SESSION['selected_user']))
 || ($_SESSION['user_role'] == 'A' && isset($_SESSION['selected_user']))) {
    $user_id = $_SESSION['selected_user'];
} else {
    $user_id = $_SESSION['user_id'];
}

// -----------------------------------------------------------------------------
// 1) Récupération de l'utilisateur / étudiant
// -----------------------------------------------------------------------------
$sql_user = "
    SELECT 
        u.*,          -- données de l'utilisateur
        e.*,          -- champs d'adresse : numAdrEtudiant, voieAdrEtudiant, etc.
        c.libClasse   -- ex. 'BTS SIO'
    FROM utilisateur u
    JOIN etudiant e ON u.idUser = e.idEtudiant
    JOIN inscription i ON e.idEtudiant = i.idEtudiant
    JOIN classe c ON i.idClasse = c.idClasse
    WHERE u.idUser = :user_id
";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_user->execute();
$user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (!$user_info) {
    die('Informations sur l\'utilisateur introuvables.');
}

// -----------------------------------------------------------------------------
// 2) Récupération du stage le plus récent + infos de l'établissement d'accueil
// -----------------------------------------------------------------------------
$sql_stage = "
    SELECT 
        s.*, 
        e.denominationEtab, e.numAdrEtab, e.voieAdrEtab, e.libAdrEtab,
        e.cpAdrEtab, e.villeAdrEtab, e.missionEtab, e.fixeEtab, e.mailEtab
    FROM stage s
    JOIN etablissement e 
      ON s.SIREN = e.SIREN
     AND s.NIC   = e.NIC
    WHERE s.idEtudiant = :user_id
    ORDER BY s.idStage DESC
    LIMIT 1
";
$stmt_stage = $conn->prepare($sql_stage);
$stmt_stage->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_stage->execute();
$stage_info = $stmt_stage->fetch(PDO::FETCH_ASSOC);

if (!$stage_info) {
    die('Informations sur le stage introuvables.');
}

// -----------------------------------------------------------------------------
// 2.1) Récupération de l'année scolaire via stage (si la colonne existe)
// -----------------------------------------------------------------------------
$annee_scolaire_stage = "";
if (!empty($stage_info['idAnneeScolaire'])) {
    $sql_annee = "
        SELECT libAnneeScolaire
        FROM anneeScolaire
        WHERE idAnneeScolaire = :id
    ";
    $stmt_annee = $conn->prepare($sql_annee);
    $stmt_annee->bindParam(':id', $stage_info['idAnneeScolaire'], PDO::PARAM_INT);
    $stmt_annee->execute();
    $row_annee = $stmt_annee->fetch(PDO::FETCH_ASSOC);
    if ($row_annee) {
        $annee_scolaire_stage = $row_annee['libAnneeScolaire'];
    }
}

// -----------------------------------------------------------------------------
// 3) Récupération du tuteur et du signataire (gérant)
// -----------------------------------------------------------------------------
$tuteur_id = $stage_info['idMaitreDeStage'];
$sql_tuteur = "SELECT * FROM contact WHERE idContact = :tuteur_id";
$stmt_tuteur = $conn->prepare($sql_tuteur);
$stmt_tuteur->bindParam(':tuteur_id', $tuteur_id, PDO::PARAM_INT);
$stmt_tuteur->execute();
$tuteur_info = $stmt_tuteur->fetch(PDO::FETCH_ASSOC);

if (!$tuteur_info) {
    die('Informations sur le tuteur introuvables.');
}

$gerant_id = $stage_info['idSignataire'];
$sql_gerant = "SELECT * FROM contact WHERE idContact = :gerant_id";
$stmt_gerant = $conn->prepare($sql_gerant);
$stmt_gerant->bindParam(':gerant_id', $gerant_id, PDO::PARAM_INT);
$stmt_gerant->execute();
$gerant_info = $stmt_gerant->fetch(PDO::FETCH_ASSOC);

// -----------------------------------------------------------------------------
// 4) Récupération de l'enseignant référent
// -----------------------------------------------------------------------------
$enseignant_referent = "Non assigné";
if (!empty($stage_info['idEnseignant'])) {
    $sql_ens = "
        SELECT u.*
        FROM enseignant e
        JOIN utilisateur u ON e.idEnseignant = u.idUser
        WHERE e.idEnseignant = :idEns
    ";
    $stmt_ens = $conn->prepare($sql_ens);
    $stmt_ens->bindParam(':idEns', $stage_info['idEnseignant'], PDO::PARAM_INT);
    $stmt_ens->execute();
    $ens_info = $stmt_ens->fetch(PDO::FETCH_ASSOC);

    if ($ens_info) {
        $enseignant_referent = 
            "<strong>" . $ens_info['prenomUser'] . " " . $ens_info['nomUser'] . "</strong><br>" .
            "<strong>Tél :</strong> " . ($ens_info['fixeUser'] ?? '') . 
            " | <strong>Email :</strong> " . ($ens_info['mailUser'] ?? '');
    }
}

// -----------------------------------------------------------------------------
// 5) Instanciation de TCPDF + paramètres
// -----------------------------------------------------------------------------
$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetMargins(10, 5, 10);
$pdf->AddPage();

// -----------------------------------------------------------------------------
// 6) En-tête (logo / titre / adresse)
// -----------------------------------------------------------------------------
$pdf->Image('img/limayraclogo.jpg', 10, 12.5, 60);

$pdf->SetXY(70, 10);
$pdf->SetFont('helvetica', 'B', 16);

$pdf->Cell(0, 10, 'CONVENTION DE STAGE', 0, 1, 'R');

// Adresse de l'établissement en plus petite police
$pdf->SetXY(10, 20);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->MultiCell(
    0,
    5,
    "50 rue de Limayrac - BP 45204\n31079 Toulouse Cedex 5\n05 61 36 08 08\naccueil@limayrac.fr\n",
    0,
    'R'
);

// Barre horizontale en dessous
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.3);
$pdf->Line(10, 45, 200, 45);

// On place le contenu principal plus bas
$pdf->SetXY(10, 50);

// -----------------------------------------------------------------------------
// 7) Informations sur l'établissement d'enseignement et l'organisme d'accueil
// -----------------------------------------------------------------------------
$infos = [
    '1 - L\'établissement d\'enseignement' =>
        "INSTITUT LIMAYRAC, 50 rue de Limayrac - BP 45204, 31079 TOULOUSE CEDEX 5\n" .
        "Représenté par Monsieur Joël LEBER, chef d'établissement\n" .
        "Tél : 05.61.36.08.08 | Email : accueil@limayrac.fr",

    '2 - L\'organisme d\'accueil' =>
        $stage_info['denominationEtab'] . " – " .
        $stage_info['numAdrEtab'] . " " . $stage_info['voieAdrEtab'] . " – " .
        $stage_info['cpAdrEtab'] . " " . $stage_info['villeAdrEtab'] . "\n" .
        "Représenté par " . $gerant_info['prenomContact'] . " " . $gerant_info['nomContact'] .
        " (fonction : " . $gerant_info['fonctionContact'] . ")\n" .
        "Tél : " . $gerant_info['fixeContact'] . " | Email : " . $gerant_info['mailContact']
];

$pdf->SetFont('helvetica', '', 10);
foreach ($infos as $title => $content) {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->MultiCell(0, 5, $title, 0, 'L', 0, 1);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 5, $content, 0, 'L', 0, 1);
    $pdf->Ln(2);
}

// -----------------------------------------------------------------------------
// 8) Tableaux / blocs colorés pour stagiaire, formation, dates, etc.
// -----------------------------------------------------------------------------
$headerFillColor = [201, 232, 255]; // Couleur de fond pour les en‑têtes

// Entête Stagiaire + Formation
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor($headerFillColor[0], $headerFillColor[1], $headerFillColor[2]);
$pdf->Cell(95, 10, '3 - Le stagiaire', 1, 0, 'L', true);
$pdf->Cell(0,  10, 'Formation suivie', 1, 1, 'L', true);

// Construire l’adresse complète de l’étudiant
$adresse_complete = $user_info['numAdrEtudiant'] . ' ' . $user_info['voieAdrEtudiant'];

if (!empty($user_info['libAdrEtudiant'])) {
    $adresse_complete .= ' ' . $user_info['libAdrEtudiant'];
}
if (!empty($user_info['lib2AdrEtudiant'])) {
    $adresse_complete .= ' ' . $user_info['lib2AdrEtudiant'];
}

// Ajouter la ligne CP + Ville
$adresse_complete .= '<br>&nbsp;' . $user_info['cpAdrEtudiant'] . ' ' . $user_info['villeAdrEtudiant'];

// HTML stagiaire
$stagiaire_html = '
<strong>Nom :</strong> ' . $user_info['nomUser'] . '<br>
<strong>Prénom :</strong> ' . $user_info['prenomUser'] . '<br>
<strong>Né(e) le :</strong> ' . date('d/m/Y', strtotime($user_info['dateNaissanceEtudiant'])) . '<br>
<strong>Adresse :</strong><br>
&nbsp;' . $adresse_complete . '<br>
<strong>Tél :</strong> ' . ($user_info['fixeUser'] ?? '') . ' | <strong>Email :</strong> ' . ($user_info['mailUser'] ?? '')
;

// Formation = classe + année scolaire du stage (si existant)
$formation_html = '
<strong>' . $user_info['libClasse'] . '</strong><br>
Année scolaire : ' . (!empty($annee_scolaire_stage) ? $annee_scolaire_stage : 'N/A') . '<br>
Adresse pendant le stage (si différent) : ' . ($stage_info['adrEtudiantStage'] ?? '')
;

// Deux colonnes
$pdf->SetFont('helvetica', '', 9);
$xStagiaire = $pdf->GetX();
$yStagiaire = $pdf->GetY();
$w1 = 95;
$h1 = 40;

$pdf->writeHTMLCell($w1, $h1, $xStagiaire, $yStagiaire, $stagiaire_html, 1, 0, false, true, 'L');
$pdf->writeHTMLCell(0,   $h1, $pdf->GetX(), $yStagiaire, $formation_html, 1, 1, false, true, 'L');

// Entête Dates du stage + Enseignant référent
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor($headerFillColor[0], $headerFillColor[1], $headerFillColor[2]);
$pdf->Cell(95, 10, 'Dates du stage', 1, 0, 'L', true);
$pdf->Cell(0,  10, "Enseignant référent à l'Institut Limayrac", 1, 1, 'L', true);

// Contenu
$date_stage_html = '
<strong>du :</strong> ' . date('d/m/Y', strtotime($stage_info['dateDebutStage'])) . 
' <strong>au :</strong> ' . date('d/m/Y', strtotime($stage_info['dateFinStage'])) . '<br>
<strong>Durée hebdo :</strong> ' . $stage_info['dureeHebdoStage'] . 'h
';
$enseignant_html = $enseignant_referent;

$pdf->SetFont('helvetica', '', 9);
$xDates = $pdf->GetX();
$yDates = $pdf->GetY();
$h2 = 20;

$pdf->writeHTMLCell(95, $h2, $xDates, $yDates, $date_stage_html, 1, 0, false, true, 'L');
$pdf->writeHTMLCell(0,  $h2, $pdf->GetX(), $yDates, $enseignant_html, 1, 1, false, true, 'L');

// Entête Maître de stage
$pdf->SetFillColor($headerFillColor[0], $headerFillColor[1], $headerFillColor[2]);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 10, "Maître de stage dans l'organisme d'accueil", 1, 1, 'L', true);

$tuteur_html = '
<strong>' . $tuteur_info['prenomContact'] . ' ' . $tuteur_info['nomContact'] . '</strong> – ' . $tuteur_info['fonctionContact'] . '<br>
<strong>Tél :</strong> ' . $tuteur_info['fixeContact'] . ' | <strong>Email :</strong> ' . $tuteur_info['mailContact']
;

$pdf->SetFont('helvetica', '', 9);
$pdf->writeHTMLCell(0, 20, '', '', $tuteur_html, 1, 1, false, true, 'L');

// -----------------------------------------------------------------------------
// 9) Ajout des articles
// -----------------------------------------------------------------------------

$articles = [
    'ARTICLE I : Objet de la convention' => "La présente convention règle les rapports de l'organisme d'accueil avec l'établissement d'enseignement et le stagiaire (et s'il est mineur son représentant légal).",
    'ARTICLE II : Objectif du stage' => "Le stage correspond à une période temporaire de mise en situation en milieu professionnel au cours de laquelle l'étudiant(e) acquiert des compétences professionnelles et met en œuvre les acquis de sa formation en vue de l'obtention d'un diplôme ou d'une certification et de favoriser son insertion professionnelle. Le stagiaire se voit confier une ou des missions conformes au projet pédagogique défini par l'Institut Limayrac et approuvées par l'organisme d'accueil. Le programme est établi par l'établissement d'enseignement et l'organisme d'accueil en fonction du programme général de la formation dispensée.\n\nActivités prévues : " . $stage_info['activitesStage'],
    'ARTICLE III : Accueil et encadrement du stagiaire' => "Le stagiaire est suivi par l'enseignant référent désigné dans la présente convention ainsi que par le service de l'établissement en charge des stages. Le tuteur de stage désigné par l'organisme d'accueil dans la présente convention est chargé d'assurer le suivi du stagiaire et d'optimiser les conditions de réalisation du stage conformément aux stipulations pédagogiques définies. Toute difficulté survenue dans la réalisation et le déroulement du stage, qu'elle soit constatée par le stagiaire ou par le tuteur de stage, doit être portée à la connaissance de l'enseignant-référent et de l'établissement d'enseignement afin d'être résolue au plus vite.",
    'ARTICLE IV : Gratification' => "Stage inférieur ou égal à 2 mois, l'étudiant peut percevoir une gratification laissée à la discrétion de l'organisme d'accueil. Stage supérieur à 2 mois, consécutifs ou non au cours d’une même année scolaire ou universitaire : l'organisme d'accueil doit verser une gratification mensuelle minimale obligatoire. Le montant horaire de la gratification est fixé à 15 % du plafond de la sécurité sociale défini en application de l’article L 241-3 du code de la sécurité sociale. Une convention de branche ou un accord professionnel peut définir un montant supérieur à ce taux. Lorsque ce montant minimal est dépassé, seul l'excédent est soumis aux charges sociales salariales et patronales. Une exception est faite à l'obligation de gratification pour les auxiliaires médicaux (Cf. Art. L124-6 alinéa 2, Art. L4381-1 du code de la santé publique). Le stagiaire a accès au restaurant d'entreprise ou aux titres-restaurants prévus à l'article L.3262-1 du code du travail, dans les mêmes conditions que les salariés de l'organisme d'accueil. Il bénéficie également de la prise en charge des frais de transport prévue à l'article L. 3261-2 du même code, le cas échéant, ainsi que les activités sociales et culturelles mentionnées à l'article L. 2323-83 du code de travail.",
    'ARTICLE V : Obligation du stagiaire' => "Durant son stage, l'étudiant est soumis à la discipline et au règlement intérieur de l'organisme d'accueil (qui doit être porté à la connaissance de l'étudiant) notamment en ce qui concerne les horaires et les règles d'hygiène et de sécurité en vigueur dans l'organisme d'accueil. Le stagiaire, pendant la durée de son stage, demeure étudiant de l'établissement d'enseignement. Tout incident devra être signalé conjointement à l'organisme d'accueil et à l'établissement d'enseignement. Toute sanction disciplinaire ne peut être décidée que par l'établissement d'enseignement. Dans ce cas, l'organisme d'accueil informe l'enseignant référent et l'établissement des manquements et fournit éventuellement les éléments constitutifs. Le stagiaire sera tenu au respect du secret professionnel. A l'issue du stage, le stagiaire peut être amené à faire un bilan avec l'organisme, à la demande écrite de celui-ci. Ce bilan peut se faire sur le lieu du stage ou à l'Institut Limayrac. Les modalités de mise en œuvre, au sein de l'entreprise, des mesures de protection définies par le protocole national en vigueur pour assurer la santé et la sécurité des salariés face à l'épidémie de Covid-19 s'appliquent à l'élève.",
    'ARTICLE VI : Absences, congés et interruption du stage' => "Toute autorisation d'absence du stagiaire devra être signalée par écrit ou par courriel par l'organisme d'accueil à l'établissement d'enseignement. Pour les absences imprévisibles, le stagiaire informera sans délai l'organisme d'accueil et l'établissement d'enseignement et leur produira ensuite un justificatif. La non-observation de ces normes pourra entraîner l'exclusion du stagiaire. Pour les stages dont la durée est supérieure à deux mois et dans la limite de la durée maximale de 6 mois, des congés ou autorisations d'absence sont possibles. Nombre de jours de congés autorisés/ou modalités des congés et autorisations d'absence durant le stage (à compléter par l'organisme). Si le stagiaire doit être présent dans l'organisme d'accueil la nuit, le dimanche ou un jour férié, préciser les cas particuliers. En cas de volonté d'une des trois parties d'interrompre définitivement le stage, celle-ci devra immédiatement en informer les deux autres parties par écrit. Les raisons invoquées seront examinées en étroite concertation. La décision définitive d'interruption du stage ne sera prise qu'à l'issue de cette phase de concertation. Le stagiaire est autorisé à revenir dans son établissement d'enseignement à la demande de celui-ci pour y suivre un cours ou passer un examen prévu dans sa formation. L'établissement de formation peut aussi convoquer l'étudiant pendant la durée du stage pour une réunion ou un conseil de classe.",
    'ARTICLE VII : Couverture sociale et assurances' => "Au cours du stage, le stagiaire bénéficie de la législation sur les accidents du travail au titre du régime étudiant de l'article L 412-8-2 du code de la Sécurité Sociale. En cas d'accident survenant au stagiaire, soit au cours des activités dans l'organisme, soit au cours du trajet, soit sur des lieux rendus utiles pour les besoins de son stage, l'organisme d'accueil doit effectuer sous 48 h la déclaration d'accident et en faire parvenir une copie à l'Institut Limayrac. Le stagiaire est couvert par un contrat d'assurance souscrit par l'Institut Limayrac auprès de la Mutuelle Saint-Christophe Assurances sous le n° 3560457104 au titre de la responsabilité civile pour les dommages tant matériels que corporels dont le stagiaire pourrait être tenu pour responsable dans le cadre du stage.",
    'ARTICLE VIII : Déplacements' => "Le stagiaire pourra éventuellement, à la demande de son responsable de stage, dans le cadre de son sujet de stage, être amené à se déplacer hors des limites de son site d'accueil, à l'intérieur, voire à l'extérieur du territoire métropolitain. Si le stagiaire accepte d'utiliser son véhicule, à la demande de l'organisme d'accueil, il doit le signaler à son assureur afin d'étendre sa garantie pour ce déplacement. La législation sur les accidents de travail s'appliquera pour les dommages corporels que subirait le stagiaire dans le cadre des déplacements précités mais par contre, les dommages causés à son véhicule ainsi que ceux qu'il pourrait provoquer relèveront de sa police d'assurance personnelle.",
    'ARTICLE IX : Prolongation de stage' => "Un avenant à la convention pourra éventuellement être établi en cas de prolongation de stage faite à la demande de l'organisme d'accueil et de l'étudiant, au-delà de la date de fin initialement fixée dans le respect de la durée maximale du stage fixée par la loi (6 mois).",
    'ARTICLE X : Suivi du stage' => "Un suivi du stagiaire (tél/visite) sera assuré durant le stage par l'enseignant référent de l'Institut Limayrac.",
    'ARTICLE XI : Fin de stage, rapport, évaluation' => "A l'issue du stage, l'organisme d'accueil délivre au stagiaire une attestation de stage et remplit une fiche ou un carnet d'évaluation qu'il retourne à l'établissement. En fonction de sa filière, l'étudiant devra fournir un rapport de stage spécifique à l'établissement (il doit prévoir un exemplaire supplémentaire destiné à l'organisme d'accueil)."
];

$pdf->Ln(5);

foreach ($articles as $title => $content) {
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->MultiCell(0, 8, $title, 0, 'L', 0, 1); 
    
    $pdf->SetFont('helvetica', '', 8.5);
    // On peut aussi réduire la hauteur de ligne, par exemple 4 au lieu de 5
    $pdf->MultiCell(0, 4, $content, 0, 'L', 0, 1);
    
    // Si on veut réduire l’espace entre deux articles
    $pdf->Ln(2);
}


// -----------------------------------------------------------------------------
// 10) Tableau de signatures (2 colonnes : label / signature vierge)
// -----------------------------------------------------------------------------
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, "Signatures", 0, 1, 'C');
$pdf->Ln(3);

$colWidth   = 95;
$rowHeight  = 25;

$pdf->SetFont('helvetica', '', 9);

// Préparation de la liste des signataires
$signatures = [
    "Mr Joël LEBER, Chef d'Etablissement" =>
        "INSTITUT LIMAYRAC\n50 rue de Limayrac – B.P. 45204\n31079 – TOULOUSE cedex 5\nTél : 05.61.36.08.08 | Email : accueil@limayrac.fr",

    "Mme " . $gerant_info['prenomContact'] . " " . $gerant_info['nomContact']
        . ", " . $gerant_info['fonctionContact'] =>
        $stage_info['denominationEtab'] . "\n" .
        $stage_info['numAdrEtab'] . " " . $stage_info['voieAdrEtab'] . "\n" .
        $stage_info['cpAdrEtab'] . " " . $stage_info['villeAdrEtab'] . "\n" .
        "Tél : " . $gerant_info['fixeContact'] . " | Email : " . $gerant_info['mailContact'],

    "Enseignant référent" =>
        (!empty($ens_info))
            ? ($ens_info['prenomUser'] . " " . $ens_info['nomUser'] . "\n" .
               "Tél : " . ($ens_info['fixeUser'] ?? '') . " | Email : " . ($ens_info['mailUser'] ?? ''))
            : "Non assigné",

    "Tuteur de stage" =>
        $tuteur_info['prenomContact'] . " " . $tuteur_info['nomContact'] . "\n" .
        "Tél : " . $tuteur_info['fixeContact'] . " | Email : " . $tuteur_info['mailContact'],

    "Le stagiaire" =>
        $user_info['nomUser'] . " " . $user_info['prenomUser'] . "\n" .
        "Tél : " . ($user_info['fixeUser'] ?? '') . " | Email : " . ($user_info['mailUser'] ?? '')
];

// Génération du tableau de 2 colonnes
foreach ($signatures as $label => $details) {
    $leftText = "" . $label . "\n" . $details;

    // Colonne gauche : infos
    $pdf->MultiCell($colWidth, $rowHeight, $leftText, 1, 'L', false, 0);
    // Colonne droite : zone vide pour la signature
    $pdf->MultiCell($colWidth, $rowHeight, '', 1, 'L', false, 1);
}

// -----------------------------------------------------------------------------
// 11) Sortie PDF
// -----------------------------------------------------------------------------
ob_end_clean();
$pdf->Output('convention_de_stage.pdf', 'I');
