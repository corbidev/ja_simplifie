<?php

declare(strict_types=1);

use EscapeGame\Config\Configuration;
use EscapeGame\Support\Uuid;

require dirname(__DIR__) . '/src/Exception/DatabaseException.php';
require dirname(__DIR__) . '/src/Config/Configuration.php';
require dirname(__DIR__) . '/src/Support/Uuid.php';

/**
 * Vérifie une condition et interrompt le test si elle est fausse.
 *
 * @param bool $condition Condition attendue.
 * @param string $message Message d'échec.
 * @return void
 */
function verifier(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$uuid = Uuid::generer();
verifier(
    preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) === 1,
    'Le UUID généré ne respecte pas le format UUID v4.'
);

$fichier = tempnam(sys_get_temp_dir(), 'escape-game-env-');

if ($fichier === false) {
    throw new RuntimeException('Impossible de créer le fichier temporaire de test.');
}

try {
    file_put_contents(
        $fichier,
        "DB_HOST=localhost\nDB_PORT=3307\nDB_NAME=jeu_test\nDB_USER=testeur\nDB_PASSWORD=\"secret de test\"\nDB_CHARSET=utf8mb4\n"
    );

    $configuration = Configuration::depuisFichier($fichier);

    verifier($configuration->hote === 'localhost', 'La lecture de DB_HOST a échoué.');
    verifier($configuration->port === 3307, 'La lecture de DB_PORT a échoué.');
    verifier($configuration->base === 'jeu_test', 'La lecture de DB_NAME a échoué.');
    verifier($configuration->motDePasse === 'secret de test', 'La lecture des guillemets .env a échoué.');

    file_put_contents($fichier, "DB_HOST=localhost\nDB_NAME=jeu_test\nDB_USER=testeur\n");
    $erreurAttendue = false;

    try {
        Configuration::depuisFichier($fichier);
    } catch (EscapeGame\Exception\DatabaseException) {
        $erreurAttendue = true;
    }

    verifier($erreurAttendue, 'Une configuration sans mot de passe aurait dû être rejetée.');
} finally {
    unlink($fichier);
}

echo "Tests réussis : UUID v4 et lecture/validation du fichier .env.\n";
