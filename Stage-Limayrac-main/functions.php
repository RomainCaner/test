<?php

function connexion()
{
    $dsn = 'mysql:host=localhost;dbname=stagebtslim_Histostage_v3';
    //$dsn = 'mysql:host=localhost;dbname=histostage_v3';
    try {
        $dbh = new PDO($dsn, 'stagebtslim_Admin', 'vMO3N?^FHs0w', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
        //$dbh = new PDO($dsn, 'root', '', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbh;
    } catch (PDOException $ex) {
        die("Erreur lors de la connexion SQL : " . $ex->getMessage());
    }
}

function pre(array $tableau)
{
    echo "<pre>";
    print_r($tableau);
    echo "</pre>";
}

function getStagesForUser($user_id)
{
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

function EtatCreationModifStage()
{
    if (isset($_SESSION['idStage'])) {
        echo '<div class="floating-bubble">';
        echo "<p><strong>Modification d'un stage existant</strong></p>";
        echo "<p><strong>idStage :</strong> " . htmlspecialchars($_SESSION['idStage']) . "</p>";
        echo '</div>';
    } else {
        echo '<div class="floating-bubble">';
        echo "<p><strong>Création d'un nouveau stage</strong></p>";
        echo '</div>';
    }
}

/**
 * Vérifie l'état de l'API SIRENE et affiche le résultat.
 *
 * @param string $siret Le numéro SIRET à tester.
 * @param string $accessToken Le jeton d'accès pour l'API.
 * @return string L'état de l'API ("Disponible" ou "Indisponible").
 */
function verifierEtatAPI_SIRENE($siret, $accessToken)
{
    $api_status = "Indisponible"; // État par défaut
    $url = "https://api.insee.fr/entreprises/sirene/V3.11/siret/$siret";

    $options = [
        'http' => [
            'header' => "Authorization: Bearer $accessToken\r\n" .
                "Accept: application/json\r\n"
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response !== false) {
        $api_status = "Disponible"; // L'API fonctionne
    }

    // Affichage de l'état de l'API
    echo "<div class='api-status " . strtolower($api_status) . "'>
    État de l'API SIRENE : <strong>$api_status</strong>
  </div>";

    return $api_status;
}



/**
 * Récupère les informations d'un établissement via l'API INSEE et les insère dans la base de données.
 *
 * @param string $siret Le numéro SIRET de l'établissement.
 * @param string $accessToken Le token d'accès pour l'API INSEE.
 * @param PDO $conn La connexion PDO à la base de données.
 * @return array Un tableau associatif contenant le statut de la requête, les données de l'établissement ou un message d'erreur.
 */
function fetchAndInsertEtablissement($siret, $accessToken, $conn)
{
    // Supprimer tous les espaces du SIRET
    $siret = preg_replace('/\s+/', '', $siret);

    // Séparation du SIRET en SIREN et NIC
    $siren = substr($siret, 0, 9);
    $nic = substr($siret, 9, 5);

    // Vérifie si l'établissement existe déjà dans la base de données
    $sql_check = "SELECT * FROM etablissement WHERE SIREN = :siren AND NIC = :nic";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bindParam(':siren', $siren, PDO::PARAM_STR);
    $stmt_check->bindParam(':nic', $nic, PDO::PARAM_STR);
    $stmt_check->execute();
    $etablissement = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($etablissement) {
        return [
            'success' => true,
            'etablissement' => $etablissement,
            'message' => "Entreprise trouvée dans la base de données. Vous pouvez confirmer votre choix."
        ];
    }

    // Si l'établissement n'existe pas, appeler l'API INSEE
    $url = "https://api.insee.fr/entreprises/sirene/V3.11/siret/$siret";

    // Configuration de la requête HTTP
    $options = [
        'http' => [
            'header' => "Authorization: Bearer $accessToken\r\n" .
                "Accept: application/json\r\n"
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        // Vérifier le code de réponse HTTP
        if (isset($http_response_header) && preg_match('/HTTP\/.*\s+(\d+)\s+.*/', $http_response_header[0], $matches)) {
            $status = intval($matches[1]);
            if ($status === 404) {
                $error = "Siret invalide.";
            } else {
                $error = "Erreur lors de la requête à l'API de l'INSEE. Code d'erreur HTTP: $status.";
            }
        } else {
            $error = "Erreur lors de la requête à l'API de l'INSEE.";
        }
        return [
            'success' => false,
            'error' => $error
        ];
    }


    $data = json_decode($response, true);

    if (!isset($data['etablissement'])) {
        return [
            'success' => false,
            'error' => "Aucun établissement trouvé avec ce SIRET."
        ];
    }

    $etablissement = $data['etablissement'];

    try {
        // Commencer une transaction pour garantir l'intégrité des données
        $conn->beginTransaction();

        // Insérer ou mettre à jour les informations dans la table organisation
        $sql_insert_org = "INSERT INTO organisation (SIREN, denominationOrg, formeJuridiqueOrg, codeApeOrg, dateCreationOrg, trancheEffectifsOrg) 
                           VALUES (:siren, :denominationOrg, :formeJuridiqueOrg, :codeApeOrg, :dateCreationOrg, :trancheEffectifsOrg)
                           ON DUPLICATE KEY UPDATE 
                               denominationOrg = VALUES(denominationOrg), 
                               formeJuridiqueOrg = VALUES(formeJuridiqueOrg), 
                               codeApeOrg = VALUES(codeApeOrg), 
                               dateCreationOrg = VALUES(dateCreationOrg), 
                               trancheEffectifsOrg = VALUES(trancheEffectifsOrg)";
        $stmt_insert_org = $conn->prepare($sql_insert_org);
        $stmt_insert_org->execute([
            ':siren' => $siren,
            ':denominationOrg' => $etablissement['uniteLegale']['denominationUniteLegale'] ?? null,
            ':formeJuridiqueOrg' => $etablissement['uniteLegale']['categorieJuridiqueUniteLegale'] ?? null,
            ':codeApeOrg' => $etablissement['uniteLegale']['activitePrincipaleUniteLegale'] ?? null,
            ':dateCreationOrg' => $etablissement['uniteLegale']['dateCreationUniteLegale'] ?? null,
            ':trancheEffectifsOrg' => $etablissement['uniteLegale']['trancheEffectifsUniteLegale'] ?? null
        ]);

        // Insérer les informations de l'établissement dans la table etablissement
        $sql_insert_etab = "INSERT INTO etablissement (SIREN, NIC, denominationEtab, numAdrEtab, voieAdrEtab, libAdrEtab, cpAdrEtab, villeAdrEtab, missionEtab, fixeEtab, mailEtab, estSiegeSocialEtab) 
                            VALUES (:siren, :nic, :denomination, :numAdr, :voieAdr, :libAdr, :cpAdr, :villeAdr, :mission, :fixe, :mail, :estSiegeSocial)";
        $stmt_insert_etab = $conn->prepare($sql_insert_etab);
        $stmt_insert_etab->execute([
            ':siren' => $siren,
            ':nic' => $nic,
            ':denomination' => $etablissement['uniteLegale']['denominationUniteLegale'] ?? null,
            ':numAdr' => $etablissement['adresseEtablissement']['numeroVoieEtablissement'] ?? null,
            ':voieAdr' => $etablissement['adresseEtablissement']['typeVoieEtablissement'] ?? null,
            ':libAdr' => $etablissement['adresseEtablissement']['libelleVoieEtablissement'] ?? null,
            ':cpAdr' => $etablissement['adresseEtablissement']['codePostalEtablissement'] ?? null,
            ':villeAdr' => $etablissement['adresseEtablissement']['libelleCommuneEtablissement'] ?? null,
            ':mission' => $etablissement['uniteLegale']['activitePrincipaleUniteLegale'] ?? null,
            ':fixe' => $etablissement['telephone'] ?? null,
            ':mail' => $etablissement['email'] ?? null,
            ':estSiegeSocial' => isset($etablissement['etablissementSiege']) ? ($etablissement['etablissementSiege'] ? '1' : '0') : '0'
        ]);

        // Valider la transaction
        $conn->commit();

        // Rechercher à nouveau l'établissement dans la base de données
        $stmt_check->execute();
        $etablissement = $stmt_check->fetch(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'etablissement' => $etablissement,
            'message' => "Entreprise trouvée et ajoutée à la base de données. Vous pouvez confirmer votre choix."
        ];
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $conn->rollBack();
        return [
            'success' => false,
            'error' => "Erreur lors de l'insertion des données dans la base de données : " . $e->getMessage()
        ];
    }
}



/**
 * Fonction pour afficher le header avec le nom de l'utilisateur
 */
function display_header()
{
    // Vérifier si l'utilisateur est connecté et récupérer ses informations
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];

        // Établir la connexion à la base de données
        $conn = connexion();

        // Préparer la requête pour récupérer les informations de l'utilisateur
        $query = "SELECT prenomUser, nomUser 
                  FROM utilisateur 
                  WHERE idUser = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_info) {
            $prenomUser = htmlspecialchars($user_info['prenomUser']);
            $nomUser = htmlspecialchars($user_info['nomUser']);
        } else {
            $prenomUser = "Utilisateur";
            $nomUser = "inconnu";
        }
    }

    // Déterminer le lien d'accueil basé sur le rôle de l'utilisateur
    if (isset($_SESSION['user_role'])) {
        switch ($_SESSION['user_role']) {
            case 'E':
                $home_link = 'index.php';
                break;
            case 'P':
            case 'A': // Les rôles 'P' et 'A' partagent le même lien d'accueil
                $home_link = 'index_prof.php';
                break;
            default:
                $home_link = 'index.php'; // Valeur par défaut
        }
    }

    // Générer le HTML du header
    echo '<header>
        <div class="container">
            <div class="header-left">
                <div class="logo">
                    <img src="img/LimayracY.png" alt="Logo" class="logo-img">
                </div>
            </div>
            <div class="header-center">
                <h1>Portail des Stages</h1>
            </div>';

    // Si l'utilisateur est connecté, afficher son nom et les liens de navigation
    if (isset($_SESSION['user_id'])) {
        echo '<div class="header-right">
            <div class="welcome-message">
                ' . htmlspecialchars($prenomUser) . ' ' . htmlspecialchars($nomUser) . '
            </div>
            <nav>
            <ul class="nav-links">';

        // Ajouter l'image entre Accueil et Déconnexion uniquement sur la page index_prof.php
        if (basename($_SERVER['PHP_SELF']) === 'index_prof.php') {
            // Si vous souhaitez afficher des liens spécifiques pour Admin, vérifiez le rôle
            if ($_SESSION['user_role'] === 'A') {
                // Icône pour la gestion des inscriptions
                echo '<li><a href="register_selection.php"><img src="img/register_icon.png" alt="Gestion des inscriptions" class="nav-icon"></a></li>';
                
               
            }
        }

        // Lien Accueil
        echo '
        <li><a href="' . htmlspecialchars($home_link) . '"><img src="img/houseW.png" alt="Accueil" class="nav-icon" title="Accueil"></a></li>
        ';

        // Lien Déconnexion
        echo '
        <li><a class="deco" href="logout.php"><img src="img/OutW.png" alt="Déconnexion" class="nav-icon" title="Déconnexion"></a></li>
            </ul>
        </nav>
          </div>';
    }
    echo '  </div>
      </header>';
}




function display_footer()
{
    $annee = date("Y");
    echo '<footer>
            <p><b>&copy; ' . $annee . '</b> Institut Limayrac. Tous droits réservés.</p>
          </footer>';
}

function display_info_etudiant($idClasse, $libAnneeScolaire)
{

    $idClasse = htmlspecialchars($idClasse);
    $libAnneeScolaire = htmlspecialchars($libAnneeScolaire);


    echo '<div class="user-info">';
    echo '<p><strong>Classe :</strong> ' . $idClasse . '</p>';
    echo '<p><strong>Année scolaire :</strong> ' . $libAnneeScolaire . '</p>';
    echo '</div>';
}

function redirect_prof()
{
    if ($_SESSION['user_role'] == 'P') { // 'P' pour Professeur
        header("Location: index_prof.php");
        exit;
    }
}

function redirect_eleve()
{
    if ($_SESSION['user_role'] == 'E') { // 'E' pour Élève
        header("Location: index.php");
        exit;
    }
}

function redirect_admin()
{
    if ($_SESSION['user_role'] == 'A') { // 'A' pour Admin
        header("Location: index_admin.php");
        exit;
    }
}

/**
 * Récupère le SIRET d'un stage en utilisant son ID.
 *
 * @param PDO $conn Connexion à la base de données.
 * @param int $idStage ID du stage.
 * @return string|null Le SIRET si trouvé, sinon null.
 */
function getSiretByIdStage(PDO $conn, int $idStage): ?string
{
    try {
        $stmt = $conn->prepare("SELECT SIREN, NIC FROM stage WHERE idStage = :idStage");
        $stmt->bindParam(':idStage', $idStage, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // Concaténer SIREN et NIC pour obtenir SIRET
            return $result['SIREN'] . $result['NIC'];
        }

        return null;
    } catch (PDOException $e) {
        // Gestion des erreurs, vous pouvez logger l'erreur
        error_log("Erreur lors de la récupération du SIRET: " . $e->getMessage());
        return null;
    }
}

function loading()
{
    ?>
        <!-- === Loader Overlay === -->
        <div id="loader-overlay" class="loader-overlay" style="display: none;">
            <div class="loader"></div>
            <img src="img/LimayracBookY.png" alt="Logo de Chargement" class="loader-logo">
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const loaderOverlay = document.getElementById('loader-overlay');

                const allLinks = document.querySelectorAll('a');
                allLinks.forEach(link => {
                    link.addEventListener('click', function(event) {
                        const href = link.getAttribute('href');
                        if (!href || href === '#') {
                            return;
                        }
                        if (
                            link.target === '_blank' ||
                            event.ctrlKey === true ||
                            event.metaKey === true
                        ) {
                            return;
                        }
                        loaderOverlay.style.display = 'flex';
                    });
                });
                const allForms = document.querySelectorAll('form');
                allForms.forEach(form => {
                    form.addEventListener('submit', function() {
                        loaderOverlay.style.display = 'flex';
                    });
                });
                window.addEventListener('pageshow', function(event) {
                    if (loaderOverlay) {
                        loaderOverlay.style.display = 'none';
                    }
                });
            });
        </script>
    <?php
}
?>
