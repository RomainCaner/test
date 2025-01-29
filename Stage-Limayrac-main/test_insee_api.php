<?php

// URL de l'API de l'INSEE avec le numéro SIREN spécifique
$url = 'https://api.insee.fr/entreprises/sirene/V3.11/siret/45250463200020';

// Token d'accès à inclure dans l'en-tête d'authentification
$accessToken = '79d04618-78db-339a-817d-bacb27c077cd';

// Configuration de la requête
$options = [
    'http' => [
        'header' => "Authorization: Bearer $accessToken\r\n" . 
                    "Accept: application/json\r\n"
    ]
];

// Création d'un contexte pour la requête
$context = stream_context_create($options);

// Exécution de la requête
$response = file_get_contents($url, false, $context);

// Vérification de la réponse
if ($response === false) {
    echo "Erreur lors de la requête à l'API de l'INSEE";
} else {
    // Affichage des données récupérées
    echo $response;
}

?>
