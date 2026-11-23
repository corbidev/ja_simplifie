<?php
declare(strict_types=1);

$racineProjet = dirname(__DIR__);

require_once $racineProjet . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'ConstantesAuthentification.php';
require_once $racineProjet . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Authentification.php';

$message = null;
$methodeRequete = $_SERVER['REQUEST_METHOD'] ?? null;
$sessionDisponible = true;

$configuration = null;

try {
    Authentification::initialiserSessionSecurisee();
} catch (RuntimeException $erreur) {
    error_log((string) $erreur);
    $sessionDisponible = false;
    $message = ConstantesAuthentification::MESSAGE_CONFIGURATION_INDISPONIBLE;
}

if ($sessionDisponible) {
    try {
        $configuration = Authentification::chargerConfiguration(
            $racineProjet . DIRECTORY_SEPARATOR . '.env'
        );
    } catch (RuntimeException $erreur) {
        error_log((string) $erreur);
        $message = ConstantesAuthentification::MESSAGE_CONFIGURATION_INDISPONIBLE;
    }
}

$destinationFournie = $methodeRequete === 'POST'
    ? array_key_exists('destination', $_POST)
    : array_key_exists('destination', $_GET);
$destinationBrute = $destinationFournie
    ? ($methodeRequete === 'POST' ? $_POST['destination'] : $_GET['destination'])
    : null;
$destinationInvalide = false;

try {
    $destination = Authentification::normaliserDestination($destinationBrute);
} catch (InvalidArgumentException) {
    $destination = null;
    $destinationInvalide = true;

    if ($message === null) {
        $message = ConstantesAuthentification::MESSAGE_DESTINATION_INVALIDE;
    }
}

if (
    $methodeRequete === 'GET'
    && $configuration !== null
    && $destination !== null
) {
    try {
        $requeteOrigine = parse_url($destinationBrute, PHP_URL_QUERY);
        $parametresOrigine = [];

        if (is_string($requeteOrigine)) {
            parse_str($requeteOrigine, $parametresOrigine);
        }

        if (array_key_exists('cle', $parametresOrigine)) {
            $identite = Authentification::determinerIdentiteDepuisCleUrl(
                $destination,
                $parametresOrigine['cle'],
                $configuration
            );

            if (
                $identite !== null
                && Authentification::destinationAutoriseePourIdentite($destination, $identite)
            ) {
                Authentification::creerSession($identite);
                $destinationFinale = Authentification::construireDestinationFinale($destination, $identite);
                Authentification::redirigerVers($destinationFinale);
            }

            $message = ConstantesAuthentification::MESSAGE_AUTHENTIFICATION_INCORRECTE;
        } else {
            $identiteSession = $_SESSION['authentification'] ?? null;

            if (
                is_array($identiteSession)
                && Authentification::destinationAutoriseePourIdentite($destination, $identiteSession)
            ) {
                $destinationFinale = Authentification::construireDestinationFinale(
                    $destination,
                    $identiteSession
                );
                Authentification::redirigerVers($destinationFinale);
            }
        }
    } catch (InvalidArgumentException | RuntimeException $erreur) {
        error_log((string) $erreur);
        $configuration = null;
        $message = ConstantesAuthentification::MESSAGE_CONFIGURATION_INDISPONIBLE;
    }
}

if ($methodeRequete === 'POST') {
    if (!$sessionDisponible) {
        $message = ConstantesAuthentification::MESSAGE_CONFIGURATION_INDISPONIBLE;
    } elseif (!Authentification::verifierJetonCsrf($_POST['jeton_csrf'] ?? null)) {
        $message = ConstantesAuthentification::MESSAGE_CSRF_INVALIDE;
    } elseif ($configuration === null) {
        $message = ConstantesAuthentification::MESSAGE_CONFIGURATION_INDISPONIBLE;
    } elseif ($destinationInvalide) {
        $message = ConstantesAuthentification::MESSAGE_DESTINATION_INVALIDE;
    } else {
        try {
            $identite = Authentification::determinerIdentiteDepuisFormulaire($_POST, $configuration);

            if ($identite === null) {
                $message = ConstantesAuthentification::MESSAGE_AUTHENTIFICATION_INCORRECTE;
            } elseif (
                $destination !== null
                && !Authentification::destinationAutoriseePourIdentite($destination, $identite)
            ) {
                $message = ConstantesAuthentification::MESSAGE_AUTHENTIFICATION_INCORRECTE;
            } else {
                Authentification::creerSession($identite);
                $destinationFinale = Authentification::construireDestinationFinale($destination, $identite);
                Authentification::redirigerVers($destinationFinale);
            }
        } catch (InvalidArgumentException | RuntimeException $erreur) {
            error_log((string) $erreur);
            $configuration = null;
            $message = ConstantesAuthentification::MESSAGE_CONFIGURATION_INDISPONIBLE;
        }
    }
}

$jetonCsrf = null;
$destinationFormulaire = null;

if ($configuration !== null && !$destinationInvalide) {
    try {
        $jetonCsrf = Authentification::genererJetonCsrf();
    } catch (Random\RandomException $erreur) {
        error_log((string) $erreur);
        $message = ConstantesAuthentification::MESSAGE_CONFIGURATION_INDISPONIBLE;
    }

    if ($destination !== null) {
        $destinationFormulaire = $destination['page'];

        if ($destination['parametres'] !== []) {
            $destinationFormulaire .= '?'
                . http_build_query($destination['parametres'], '', '&', PHP_QUERY_RFC3986);
        }
    }
}
?>
<!doctype html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title><?= ConstantesAuthentification::TITRE_PAGE ?></title>
    <link rel="stylesheet" href="assets/css/login.css">
    <script src="assets/js/login.js" defer></script>
</head>
<body>
    <div class="page-shell">
        <header class="page-header">
            <div class="marque">
                <span class="marque-symbole" aria-hidden="true"></span>
                <p class="marque-texte"><?= ConstantesAuthentification::NOM_APPLICATION ?></p>
            </div>
            <button
                class="bouton-theme"
                type="button"
                data-bascule-theme
                data-libelle-sombre="<?= htmlspecialchars(ConstantesAuthentification::LIBELLE_THEME_SOMBRE, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                data-libelle-clair="<?= htmlspecialchars(ConstantesAuthentification::LIBELLE_THEME_CLAIR, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                aria-label="<?= htmlspecialchars(ConstantesAuthentification::LIBELLE_ACCESSIBLE_BOUTON_THEME, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                aria-pressed="true"
            >
                <span class="bouton-theme-symbole" aria-hidden="true"></span>
                <span data-libelle-theme><?= ConstantesAuthentification::LIBELLE_THEME_SOMBRE ?></span>
            </button>
        </header>

        <main>
            <section class="intro" aria-labelledby="titre-connexion">
                <p class="intro-surTitre"><?= ConstantesAuthentification::NOM_APPLICATION ?></p>
                <h1 id="titre-connexion"><?= ConstantesAuthentification::TITRE_PAGE ?></h1>
                <p class="intro-description"><?= ConstantesAuthentification::TEXTE_INTRODUCTION ?></p>
            </section>

            <?php if ($message !== null): ?>
                <div class="message-erreur" role="alert">
                    <?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($jetonCsrf !== null): ?>
                <section
                    class="grille-connexion"
                    aria-label="<?= htmlspecialchars(ConstantesAuthentification::LIBELLE_ACCESSIBLE_ESPACES_CONNEXION, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                >
                    <article class="carte-connexion">
                        <form method="post" action="login.php" aria-labelledby="titre-equipe">
                            <input
                                type="hidden"
                                name="jeton_csrf"
                                value="<?= htmlspecialchars($jetonCsrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                            >
                            <?php if ($destinationFormulaire !== null): ?>
                                <input
                                    type="hidden"
                                    name="destination"
                                    value="<?= htmlspecialchars($destinationFormulaire, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                                >
                            <?php endif; ?>
                            <div class="carte-entete">
                                <span class="carte-index" aria-hidden="true">01</span>
                                <h2 id="titre-equipe"><?= ConstantesAuthentification::TITRE_CONNEXION_EQUIPE ?></h2>
                                <p class="carte-description"><?= ConstantesAuthentification::AIDE_CONNEXION_EQUIPE ?></p>
                            </div>
                            <div class="champs">
                                <div class="champ">
                                    <label for="identifiant-equipe"><?= ConstantesAuthentification::LIBELLE_IDENTIFIANT_EQUIPE ?></label>
                                    <input
                                        id="identifiant-equipe"
                                        name="identifiant"
                                        type="text"
                                        autocomplete="username"
                                        required
                                    >
                                </div>
                                <div class="champ">
                                    <label for="cle-equipe"><?= ConstantesAuthentification::LIBELLE_CLE_EQUIPE ?></label>
                                    <input
                                        id="cle-equipe"
                                        name="cle"
                                        type="password"
                                        autocomplete="current-password"
                                        required
                                    >
                                </div>
                            </div>
                            <button class="bouton-connexion" type="submit"><?= ConstantesAuthentification::LIBELLE_BOUTON_CONNEXION ?></button>
                        </form>
                    </article>

                    <article class="carte-connexion">
                        <form method="post" action="login.php" aria-labelledby="titre-tableau-de-bord">
                            <input
                                type="hidden"
                                name="jeton_csrf"
                                value="<?= htmlspecialchars($jetonCsrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                            >
                            <?php if ($destinationFormulaire !== null): ?>
                                <input
                                    type="hidden"
                                    name="destination"
                                    value="<?= htmlspecialchars($destinationFormulaire, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                                >
                            <?php endif; ?>
                            <div class="carte-entete">
                                <span class="carte-index" aria-hidden="true">02</span>
                                <h2 id="titre-tableau-de-bord"><?= ConstantesAuthentification::TITRE_ACCES_TABLEAU_DE_BORD ?></h2>
                                <p class="carte-description"><?= ConstantesAuthentification::AIDE_ACCES_TABLEAU_DE_BORD ?></p>
                            </div>
                            <div class="champs">
                                <div class="champ">
                                    <label for="cle-tableau-de-bord"><?= ConstantesAuthentification::LIBELLE_CLE_TABLEAU_DE_BORD ?></label>
                                    <input
                                        id="cle-tableau-de-bord"
                                        name="cle"
                                        type="password"
                                        autocomplete="current-password"
                                        required
                                    >
                                </div>
                            </div>
                            <button class="bouton-connexion" type="submit"><?= ConstantesAuthentification::LIBELLE_BOUTON_CONNEXION ?></button>
                        </form>
                    </article>

                    <article class="carte-connexion">
                        <form method="post" action="login.php" aria-labelledby="titre-administrateur">
                            <input
                                type="hidden"
                                name="jeton_csrf"
                                value="<?= htmlspecialchars($jetonCsrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                            >
                            <?php if ($destinationFormulaire !== null): ?>
                                <input
                                    type="hidden"
                                    name="destination"
                                    value="<?= htmlspecialchars($destinationFormulaire, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                                >
                            <?php endif; ?>
                            <div class="carte-entete">
                                <span class="carte-index" aria-hidden="true">03</span>
                                <h2 id="titre-administrateur"><?= ConstantesAuthentification::TITRE_CONNEXION_ADMINISTRATEUR ?></h2>
                                <p class="carte-description"><?= ConstantesAuthentification::AIDE_CONNEXION_ADMINISTRATEUR ?></p>
                            </div>
                            <div class="champs">
                                <div class="champ">
                                    <label for="identifiant-administrateur"><?= ConstantesAuthentification::LIBELLE_IDENTIFIANT_ADMINISTRATEUR ?></label>
                                    <input
                                        id="identifiant-administrateur"
                                        name="identifiant"
                                        type="text"
                                        autocomplete="username"
                                        required
                                    >
                                </div>
                                <div class="champ">
                                    <label for="mot-de-passe-administrateur"><?= ConstantesAuthentification::LIBELLE_MOT_DE_PASSE ?></label>
                                    <input
                                        id="mot-de-passe-administrateur"
                                        name="mot_de_passe"
                                        type="password"
                                        autocomplete="current-password"
                                        required
                                    >
                                </div>
                            </div>
                            <button class="bouton-connexion" type="submit"><?= ConstantesAuthentification::LIBELLE_BOUTON_CONNEXION ?></button>
                        </form>
                    </article>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
