<?php
declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'ConstantesAuthentification.php';
?>
<!doctype html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title><?= ConstantesAuthentification::TITRE_ACCUEIL ?></title>
    <link rel="stylesheet" href="assets/css/login.css">
    <script src="assets/js/login.js" defer></script>
</head>
<body>
    <div class="page-shell page-shell-accueil">
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

        <main class="accueil">
            <section class="intro" aria-labelledby="titre-accueil">
                <p class="intro-surTitre"><?= ConstantesAuthentification::NOM_APPLICATION ?></p>
                <h1 id="titre-accueil"><?= ConstantesAuthentification::TITRE_ACCUEIL ?></h1>
                <p class="intro-description"><?= ConstantesAuthentification::TEXTE_ACCUEIL ?></p>
                <a class="lien-accueil" href="login.php">
                    <?= ConstantesAuthentification::LIBELLE_BOUTON_ACCEDER_ESPACES ?>
                </a>
            </section>
        </main>
    </div>
</body>
</html>