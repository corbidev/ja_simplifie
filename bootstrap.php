<?php

declare(strict_types=1);

/**
 * Initialise le chargement des classes et expose la connexion à la base.
 *
 * @return PDO Connexion PDO configurée.
 * @throws \EscapeGame\Exception\DatabaseException Si la configuration ou la connexion échoue.
 */

use EscapeGame\Config\Configuration;
use EscapeGame\Database\Connexion;

spl_autoload_register(
    static function (string $classe): void {
        $prefixe = 'EscapeGame\\';
        $repertoire = __DIR__ . '/src/';

        if (!str_starts_with($classe, $prefixe)) {
            return;
        }

        $cheminRelatif = str_replace('\\', DIRECTORY_SEPARATOR, substr($classe, strlen($prefixe)));
        $fichier = $repertoire . $cheminRelatif . '.php';

        if (is_file($fichier)) {
            require_once $fichier;
        }
    }
);

$configuration = Configuration::depuisFichier(__DIR__ . '/.env');

return Connexion::creer($configuration);
