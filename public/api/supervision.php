<?php

declare(strict_types=1);

use EscapeGame\Api\ApiException;
use EscapeGame\Api\AuthentificationApi;
use EscapeGame\Api\ReponseJson;
use EscapeGame\Service\SupervisionService;

$racineProjet = dirname(__DIR__, 2);

// Les contrôles de session doivent précéder bootstrap.php, qui ouvre la connexion PDO.
require_once $racineProjet . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Authentification.php';
require_once $racineProjet . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Api' . DIRECTORY_SEPARATOR . 'ApiException.php';
require_once $racineProjet . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Api' . DIRECTORY_SEPARATOR . 'AuthentificationApi.php';
require_once $racineProjet . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Api' . DIRECTORY_SEPARATOR . 'ReponseJson.php';

try {
    $identite = AuthentificationApi::obtenirIdentite();
    $methode = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    if ($methode === 'GET') {
        AuthentificationApi::exigerRole($identite, ['administrateur', 'tableau_de_bord', 'equipe']);

        $action = $_GET['action'] ?? 'etat';
        if ($action !== 'etat') {
            throw new ApiException('Cette action de consultation est inconnue.', 404);
        }

        $partieId = $_GET['partie_id'] ?? null;
        if (!is_string($partieId) || !preg_match('/\A[0-9a-fA-F-]{36}\z/', $partieId)) {
            throw new ApiException('Un identifiant de partie valide est requis.');
        }

        $connexion = require $racineProjet . DIRECTORY_SEPARATOR . 'bootstrap.php';
        $service = new SupervisionService($connexion);
        $numeroEquipe = $identite['role'] === 'equipe' ? $identite['equipe'] : null;
        $etat = $service->obtenirEtat($partieId, $numeroEquipe, $identite['role'] === 'administrateur');
        ReponseJson::envoyer(['succes' => true, 'donnees' => $etat]);
    }

    if ($methode !== 'POST') {
        header('Allow: GET, POST');
        throw new ApiException('Méthode HTTP non autorisée.', 405);
    }

    AuthentificationApi::exigerRole($identite, ['administrateur']);
    AuthentificationApi::verifierCsrf();

    $contenu = file_get_contents('php://input');
    if ($contenu === false || $contenu === '') {
        throw new ApiException('Le corps JSON de la requête est obligatoire.');
    }

    try {
        $requete = json_decode($contenu, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new ApiException('Le corps de la requête doit être un JSON valide.', 400, $exception);
    }

    if (!is_array($requete)) {
        throw new ApiException('La structure du corps JSON est invalide.');
    }

    $action = $requete['action'] ?? null;
    $partieId = $requete['partie_id'] ?? null;
    if (!is_string($partieId) || !preg_match('/\A[0-9a-fA-F-]{36}\z/', $partieId)) {
        throw new ApiException('Un identifiant de partie valide est requis.');
    }

    // Le chargement du bootstrap active l'autoloader et ouvre PDO seulement
    // après validation de la session, du rôle, du CSRF et du corps de requête.
    $connexion = require $racineProjet . DIRECTORY_SEPARATOR . 'bootstrap.php';
    $service = new SupervisionService($connexion);

    switch ($action) {
        case 'partie.modifier_etat':
            if (!is_string($requete['etat'] ?? null)) {
                throw new ApiException('L’état de la partie est obligatoire.');
            }
            $service->modifierEtatPartie($partieId, $requete['etat']);
            ReponseJson::envoyer(['succes' => true]);

        case 'equipe.modifier_suivi':
            $equipeId = $requete['equipe_id'] ?? null;
            $donnees = $requete['donnees'] ?? null;
            if (!is_string($equipeId) || !preg_match('/\A[0-9a-fA-F-]{36}\z/', $equipeId) || !is_array($donnees)) {
                throw new ApiException('Les paramètres de mise à jour de l’équipe sont invalides.');
            }
            $service->modifierSuiviEquipe($partieId, $equipeId, $donnees);
            ReponseJson::envoyer(['succes' => true]);

        case 'notification.creer':
            foreach (['type_destinataire', 'type_notification', 'contenu'] as $champ) {
                if (!is_string($requete[$champ] ?? null)) {
                    throw new ApiException('Les paramètres de notification sont incomplets.');
                }
            }
            $id = $service->creerNotification(
                $partieId,
                $requete['type_destinataire'],
                $requete['type_notification'],
                $requete['contenu'],
                is_string($requete['equipe_id'] ?? null) ? $requete['equipe_id'] : null,
                is_string($requete['groupe_id'] ?? null) ? $requete['groupe_id'] : null
            );
            ReponseJson::envoyer(['succes' => true, 'notification_id' => $id], 201);

        case 'notification.marquer_lue':
            $notificationId = $requete['notification_id'] ?? null;
            if (!is_string($notificationId) || !preg_match('/\A[0-9a-fA-F-]{36}\z/', $notificationId)) {
                throw new ApiException('L’identifiant de notification est invalide.');
            }
            $service->marquerNotificationLue($partieId, $notificationId);
            ReponseJson::envoyer(['succes' => true]);

        default:
            throw new ApiException('Cette action est inconnue.', 404);
    }
} catch (ApiException $exception) {
    ReponseJson::envoyer(['succes' => false, 'erreur' => $exception->getMessage()], $exception->statutHttp);
} catch (Throwable $exception) {
    error_log('[API supervision] Une erreur interne est survenue.');
    ReponseJson::envoyer(['succes' => false, 'erreur' => 'Une erreur interne est survenue.'], 500);
}
