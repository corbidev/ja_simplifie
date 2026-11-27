<?php

declare(strict_types=1);

namespace EscapeGame\Database;

use EscapeGame\Config\Configuration;
use EscapeGame\Exception\DatabaseException;
use PDO;
use PDOException;

/**
 * Centralise la création de la connexion PDO vers MariaDB.
 */
final class Connexion
{
    /**
     * Crée une connexion PDO configurée pour utiliser des requêtes préparées.
     *
     * @param Configuration $configuration Paramètres de connexion.
     * @return PDO Connexion ouverte.
     * @throws DatabaseException Si le pilote PDO MySQL est absent ou si la connexion échoue.
     */
    public static function creer(Configuration $configuration): PDO
    {
        if (!extension_loaded('pdo_mysql')) {
            throw new DatabaseException('L’extension PHP pdo_mysql doit être activée.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $configuration->hote,
            $configuration->port,
            $configuration->base,
            $configuration->jeuCaracteres
        );

        try {
            return new PDO(
                $dsn,
                $configuration->utilisateur,
                $configuration->motDePasse,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ]
            );
        } catch (PDOException $exception) {
            throw new DatabaseException(
                'La connexion à la base de données a échoué. Vérifier la configuration et les droits MariaDB.',
                (int) $exception->getCode(),
                $exception
            );
        }
    }
}
