<?php

declare(strict_types=1);

namespace EscapeGame\Repository;

use PDO;

/**
 * Gère les données de suivi d'une équipe pendant une partie.
 */
final class PartieEquipeRepository extends AbstractRepository
{
    /**
     * Initialise le suivi d'une équipe dans une partie.
     *
     * @param string $partieId Identifiant UUID de la partie.
     * @param string $equipeId Identifiant UUID de l'équipe.
     * @return void
     */
    public function creer(string $partieId, string $equipeId): void
    {
        $this->executer(
            'INSERT INTO partie_equipes (partie_id, equipe_id)
             VALUES (:partie_id, :equipe_id)',
            ['partie_id' => $partieId, 'equipe_id' => $equipeId]
        );
    }

    /**
     * Recherche les données de suivi d'une équipe dans une partie.
     *
     * @param string $partieId Identifiant UUID de la partie.
     * @param string $equipeId Identifiant UUID de l'équipe.
     * @return array<string, mixed>|null Suivi trouvé ou null.
     */
    public function obtenir(string $partieId, string $equipeId): ?array
    {
        $suivi = $this->executer(
            'SELECT * FROM partie_equipes WHERE partie_id = :partie_id AND equipe_id = :equipe_id LIMIT 1',
            ['partie_id' => $partieId, 'equipe_id' => $equipeId]
        )->fetch();

        return $suivi === false ? null : $suivi;
    }

    /**
     * Retourne le suivi de toutes les équipes d'une partie.
     *
     * @param string $partieId Identifiant UUID de la partie.
     * @return list<array<string, mixed>> Suivis de la partie.
     */
    public function obtenirParPartie(string $partieId): array
    {
        return $this->executer(
            'SELECT * FROM partie_equipes WHERE partie_id = :partie_id ORDER BY equipe_id',
            ['partie_id' => $partieId]
        )->fetchAll();
    }

    /**
     * Modifie les informations de suivi autorisées.
     *
     * @param string $partieId Identifiant UUID de la partie.
     * @param string $equipeId Identifiant UUID de l'équipe.
     * @param array<string, mixed> $donnees Colonnes et valeurs à modifier.
     * @return int Nombre de lignes affectées.
     */
    public function modifier(string $partieId, string $equipeId, array $donnees): int
    {
        $autorises = [
            'nombre_tentatives_porte', 'etat_porte', 'porte_ouverte',
            'etat_resolution_annee', 'annee_saisie', 'instant_validation_correcte',
            'temps_resolution_secondes',
        ];
        $this->verifierChamps($donnees, $autorises);

        $affectations = [];
        $parametres = ['partie_id' => $partieId, 'equipe_id' => $equipeId];

        foreach ($donnees as $champ => $valeur) {
            $affectations[] = $champ . ' = :' . $champ;
            $parametres[$champ] = $valeur;
        }

        return $this->executer(
            'UPDATE partie_equipes SET ' . implode(', ', $affectations)
            . ' WHERE partie_id = :partie_id AND equipe_id = :equipe_id',
            $parametres
        )->rowCount();
    }

    /**
     * Incrémente atomiquement le nombre de tentatives de code de porte.
     *
     * @param string $partieId Identifiant UUID de la partie.
     * @param string $equipeId Identifiant UUID de l'équipe.
     * @return int Nombre de lignes affectées.
     */
    public function incrementerTentativesPorte(string $partieId, string $equipeId): int
    {
        return $this->executer(
            'UPDATE partie_equipes
             SET nombre_tentatives_porte = nombre_tentatives_porte + 1
             WHERE partie_id = :partie_id AND equipe_id = :equipe_id',
            ['partie_id' => $partieId, 'equipe_id' => $equipeId]
        )->rowCount();
    }
}
