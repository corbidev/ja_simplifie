<?php

declare(strict_types=1);

namespace EscapeGame\Config;

use EscapeGame\Exception\DatabaseException;

/**
 * Charge et expose les paramètres de connexion définis dans un fichier .env.
 */
final class Configuration
{
    /**
     * Construit une configuration de connexion.
     *
     * @param string $hote Adresse du serveur MariaDB.
     * @param int $port Port TCP du serveur MariaDB.
     * @param string $base Nom de la base de données.
     * @param string $jeuCaracteres Jeu de caractères de la connexion.
     * @param string $utilisateur Utilisateur MariaDB.
     * @param string $motDePasse Mot de passe MariaDB.
     */
    private function __construct(
        public readonly string $hote,
        public readonly int $port,
        public readonly string $base,
        public readonly string $jeuCaracteres,
        public readonly string $utilisateur,
        public readonly string $motDePasse
    ) {
    }

    /**
     * Lit un fichier .env et vérifie les paramètres indispensables.
     *
     * @param string $chemin Chemin absolu ou relatif du fichier .env.
     * @return self Configuration prête à être utilisée.
     * @throws DatabaseException Si le fichier ou une valeur obligatoire est absent ou invalide.
     */
    public static function depuisFichier(string $chemin): self
    {
        if (!is_file($chemin) || !is_readable($chemin)) {
            throw new DatabaseException('Le fichier de configuration .env est introuvable ou illisible.');
        }

        $valeurs = [];
        $lignes = file($chemin, FILE_IGNORE_NEW_LINES);

        if ($lignes === false) {
            throw new DatabaseException('Le fichier de configuration .env ne peut pas être lu.');
        }

        foreach ($lignes as $numero => $ligne) {
            $ligne = trim($ligne);

            if ($ligne === '' || str_starts_with($ligne, '#')) {
                continue;
            }

            $separateur = strpos($ligne, '=');

            if ($separateur === false) {
                throw new DatabaseException('Format invalide dans le fichier .env à la ligne ' . ($numero + 1) . '.');
            }

            $cle = trim(substr($ligne, 0, $separateur));
            $valeur = trim(substr($ligne, $separateur + 1));

            if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $cle)) {
                throw new DatabaseException('Nom de paramètre invalide dans le fichier .env à la ligne ' . ($numero + 1) . '.');
            }

            if (strlen($valeur) >= 2) {
                $premier = $valeur[0];
                $dernier = $valeur[strlen($valeur) - 1];

                if (($premier === '"' && $dernier === '"') || ($premier === "'" && $dernier === "'")) {
                    $valeur = substr($valeur, 1, -1);
                }
            }

            $valeurs[$cle] = $valeur;
        }

        $obligatoires = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];

        foreach ($obligatoires as $cle) {
            if (!array_key_exists($cle, $valeurs) || $valeurs[$cle] === '') {
                throw new DatabaseException('Le paramètre ' . $cle . ' est obligatoire dans le fichier .env.');
            }
        }

        $port = filter_var($valeurs['DB_PORT'] ?? '3306', FILTER_VALIDATE_INT);

        if ($port === false || $port < 1 || $port > 65535) {
            throw new DatabaseException('Le paramètre DB_PORT doit être un port TCP valide.');
        }

        $charset = $valeurs['DB_CHARSET'] ?? 'utf8mb4';

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $charset)) {
            throw new DatabaseException('Le paramètre DB_CHARSET contient une valeur invalide.');
        }

        return new self(
            $valeurs['DB_HOST'],
            $port,
            $valeurs['DB_NAME'],
            $charset,
            $valeurs['DB_USER'],
            $valeurs['DB_PASSWORD']
        );
    }
}
