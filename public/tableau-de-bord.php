<?php

declare(strict_types=1);

/**
 * Prépare les données publiques autorisées pour le tableau de bord.
 *
 * Les fichiers JSON sont lus depuis le dossier privé « data », situé à la
 * racine du projet, en dehors du dossier public.
 * Les informations historiques ne sont ajoutées qu'après résolution de l'année.
 *
 * @return array<string, mixed> Données publiques de la partie et des quatre équipes.
 *
 * @throws RuntimeException Si un fichier est inaccessible ou contient des données invalides.
 */
function preparerDonneesTableauDeBord(): array
{
    $dossierDonnees = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;

    /**
     * Lit et décode un fichier JSON privé.
     *
     * @param string $chemin Chemin absolu du fichier JSON.
     *
     * @return array<string, mixed> Contenu JSON décodé en tableau associatif.
     *
     * @throws RuntimeException Si le fichier est illisible ou si son JSON est invalide.
     */
    $lireJson = static function (string $chemin): array {
        if (!is_file($chemin) || !is_readable($chemin)) {
            throw new RuntimeException('Un fichier de données requis est indisponible.');
        }

        $contenu = file_get_contents($chemin);
        if ($contenu === false) {
            throw new RuntimeException('La lecture d’un fichier de données a échoué.');
        }

        try {
            $donnees = json_decode($contenu, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Un fichier de données contient un JSON invalide.', 0, $exception);
        }

        if (!is_array($donnees)) {
            throw new RuntimeException('La structure d’un fichier de données est invalide.');
        }

        return $donnees;
    };

    $suiviPartie = $lireJson($dossierDonnees . 'suivi-partie.json');
    if (!isset($suiviPartie['partie']['etat'], $suiviPartie['partie']['chronometrage'], $suiviPartie['equipes'])
        || !is_array($suiviPartie['partie']['chronometrage'])
        || !is_array($suiviPartie['equipes'])) {
        throw new RuntimeException('La structure du suivi de partie est invalide.');
    }

    $equipesPubliques = [];

    for ($numeroEquipe = 1; $numeroEquipe <= 4; $numeroEquipe++) {
        $donneesEquipe = $lireJson($dossierDonnees . 'equipe-' . $numeroEquipe . '.json');
        $suiviEquipe = $suiviPartie['equipes'][(string) $numeroEquipe] ?? null;

        if (!is_array($suiviEquipe)
            || !isset($donneesEquipe['equipe'], $suiviEquipe['etat_porte'], $suiviEquipe['etat_resolution_annee'])
            || (int) $donneesEquipe['equipe'] !== $numeroEquipe) {
            throw new RuntimeException('Les données d’une équipe sont invalides.');
        }

        $equipePublique = [
            'equipe' => $numeroEquipe,
            'etat_porte' => (string) $suiviEquipe['etat_porte'],
            'porte_ouverte' => (bool) ($suiviEquipe['porte_ouverte'] ?? false),
            'nombre_tentatives_porte' => max(0, (int) ($suiviEquipe['nombre_tentatives_porte'] ?? 0)),
            'etat_resolution_annee' => (string) $suiviEquipe['etat_resolution_annee'],
        ];

        if ($equipePublique['etat_resolution_annee'] === 'resolue') {
            $referenceHistorique = $donneesEquipe['reference_historique'] ?? null;

            if (!is_array($referenceHistorique)
                || !isset($referenceHistorique['theme'], $referenceHistorique['annee_mentionnee_dans_le_cadrage'])
                || !is_string($referenceHistorique['theme'])
                || !is_numeric($referenceHistorique['annee_mentionnee_dans_le_cadrage'])) {
                throw new RuntimeException('Les données historiques d’une équipe résolue sont invalides.');
            }

            $equipePublique['phrase'] = $referenceHistorique['theme'];
            $equipePublique['annee'] = (int) $referenceHistorique['annee_mentionnee_dans_le_cadrage'];
        }

        $equipesPubliques[] = $equipePublique;
    }

    return [
        'partie' => [
            'etat' => (string) $suiviPartie['partie']['etat'],
            'chronometrage' => $suiviPartie['partie']['chronometrage'],
        ],
        'equipes' => $equipesPubliques,
    ];
}

$erreurChargement = false;
try {
    $donneesTableauDeBord = preparerDonneesTableauDeBord();
} catch (RuntimeException $exception) {
    // Le détail technique reste dans les journaux serveur, jamais dans la page publique.
    error_log('[Tableau de bord] ' . $exception->getMessage());
    $erreurChargement = true;
    $donneesTableauDeBord = null;
}

$jsonTableauDeBord = json_encode(
    $donneesTableauDeBord,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
);

if ($jsonTableauDeBord === false) {
    $erreurChargement = true;
    $jsonTableauDeBord = 'null';
    error_log('[Tableau de bord] Impossible d’encoder les données publiques en JSON.');
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#050816">
<title>Tableau de supervision — JA 2026</title>
<link rel="stylesheet" href="assets/css/tableau-de-bord.css">
</head>
<body>
<canvas id="scene3d" aria-hidden="true"></canvas><div class="grain" aria-hidden="true"></div>
<main class="interface" aria-label="Tableau de supervision des quatre équipes">
  <header class="topline">
    <section class="clock-unit" aria-label="Horloge numérique à LED rouges">
      <div class="clock-face" aria-hidden="true"><svg id="clockRing" class="clock-ring" viewBox="0 0 100 100"></svg><div id="horloge" class="clock-digital" aria-label="Heure"></div></div>
    </section>
    <section class="led-shell" aria-label="Bandeau LED du chronomètre"><div class="led-screen"><canvas id="ledCanvas" aria-label="Chronomètre : durée non configurée"></canvas></div></section>
  </header>
  <section id="equipes" class="team-grid" aria-label="État des équipes"></section>
</main>
<script id="donnees-tableau-de-bord" type="application/json"><?= $jsonTableauDeBord ?></script>
<script>
    window.TABLEAU_DE_BORD_ERREUR_CHARGEMENT = <?= $erreurChargement ? 'true' : 'false' ?>;
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r160/three.min.js"></script>
<script src="assets/js/tableau-de-bord.js" defer></script>
</body>
</html>
