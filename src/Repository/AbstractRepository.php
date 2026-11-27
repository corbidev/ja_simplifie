<?php

declare(strict_types=1);

namespace EscapeGame\Repository;

use EscapeGame\Exception\DatabaseException;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

/**
 * Fournit les opérations SQL communes aux repositories du projet.
 */
abstract class AbstractRepository
{
    /**
     * Initialise le repository avec la connexion partagée.
     *
     * @param PDO $connexion Connexion PDO active.
     */
    public function __construct(protected readonly PDO $connexion)
    {
    }

    /**
     * Exécute une requête préparée avec des paramètres explicitement typés.
     *
     * @param string $sql Requête SQL dont les identifiants sont définis par le code.
     * @param array<string, mixed> $parametres Paramètres nommés de la requête.
     * @return PDOStatement Résultat de l'exécution.
     * @throws DatabaseException Si l'exécution SQL échoue.
     */
    protected function executer(string $sql, array $parametres = []): PDOStatement
    {
        try {
            $requete = $this->connexion->prepare($sql);

            foreach ($parametres as $nom => $valeur) {
                $type = match (true) {
                    is_int($valeur) => PDO::PARAM_INT,
                    is_bool($valeur) => PDO::PARAM_BOOL,
                    $valeur === null => PDO::PARAM_NULL,
                    default => PDO::PARAM_STR,
                };

                $requete->bindValue(':' . ltrim((string) $nom, ':'), $valeur, $type);
            }

            $requete->execute();

            return $requete;
        } catch (PDOException $exception) {
            throw new DatabaseException('Une opération SQL a échoué.', (int) $exception->getCode(), $exception);
        }
    }

    /**
     * Encode un tableau PHP destiné à une colonne JSON.
     *
     * @param array<string, mixed> $valeur Données à encoder.
     * @return string Représentation JSON UTF-8.
     * @throws DatabaseException Si les données ne peuvent pas être encodées.
     */
    protected function encoderJson(array $valeur): string
    {
        try {
            return json_encode($valeur, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $exception) {
            throw new DatabaseException('Les données fournies ne peuvent pas être converties en JSON.', 0, $exception);
        }
    }

    /**
     * Convertit une valeur JSON stockée en tableau PHP.
     *
     * @param string|null $valeur Contenu JSON de la base.
     * @return array<string, mixed>|null Tableau décodé ou null.
     * @throws DatabaseException Si le contenu JSON est invalide.
     */
    protected function decoderJson(?string $valeur): ?array
    {
        if ($valeur === null) {
            return null;
        }

        try {
            $resultat = json_decode($valeur, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new DatabaseException('Une valeur JSON stockée en base est invalide.', 0, $exception);
        }

        if (!is_array($resultat)) {
            throw new DatabaseException('Une valeur JSON stockée en base ne représente pas un objet ou un tableau.');
        }

        return $resultat;
    }

    /**
     * Vérifie que les champs d'une mise à jour appartiennent à une liste autorisée.
     *
     * @param array<string, mixed> $donnees Données à modifier.
     * @param list<string> $champsAutorises Colonnes modifiables.
     * @return void
     * @throws DatabaseException Si un champ est interdit ou si aucune donnée n'est fournie.
     */
    protected function verifierChamps(array $donnees, array $champsAutorises): void
    {
        if ($donnees === []) {
            throw new DatabaseException('Aucune donnée à modifier.');
        }

        foreach (array_keys($donnees) as $champ) {
            if (!in_array($champ, $champsAutorises, true)) {
                throw new DatabaseException('Le champ demandé ne peut pas être modifié : ' . $champ);
            }
        }
    }
}
