<?php

declare(strict_types=1);

namespace EscapeGame\Repository;

use EscapeGame\Support\Uuid;

/**
 * Gère la lecture, la création et la modification des parties.
 */
final class PartieRepository extends AbstractRepository
{
    /**
     * Crée une partie en attente.
     *
     * @param array<string, mixed> $donnees Valeurs initiales autorisées de la partie.
     * @return string Identifiant UUID de la partie créée.
     */
    public function creer(array $donnees = []): string
    {
        $id = $donnees['id'] ?? Uuid::generer();
        $etat = $donnees['etat'] ?? 'en_attente';
        $duree = $donnees['duree_total_compte_a_rebours_secondes'] ?? null;

        $this->executer(
            'INSERT INTO parties (id, etat, duree_total_compte_a_rebours_secondes) 
             VALUES (:id, :etat, :duree)',
            ['id' => $id, 'etat' => $etat, 'duree' => $duree]
        );

        return $id;
    }

    /**
     * Recherche une partie par son identifiant.
     *
     * @param string $id Identifiant UUID de la partie.
     * @return array<string, mixed>|null Partie trouvée ou null.
     */
    public function obtenirParId(string $id): ?array
    {
        $resultat = $this->executer(
            'SELECT * FROM parties WHERE id = :id LIMIT 1',
            ['id' => $id]
        )->fetch();

        return $resultat === false ? null : $resultat;
    }

    /**
     * Retourne les parties, de la plus récente à la plus ancienne.
     *
     * @param int $limite Nombre maximal de résultats.
     * @return list<array<string, mixed>> Liste des parties.
     */
    public function obtenirToutes(int $limite = 100): array
    {
        $requete = $this->executer(
            'SELECT * FROM parties ORDER BY date_creation DESC LIMIT :limite',
            ['limite' => max(1, min($limite, 500))]
        );

        return $requete->fetchAll();
    }

    /**
     * Modifie les informations autorisées d'une partie.
     *
     * @param string $id Identifiant UUID de la partie.
     * @param array<string, mixed> $donnees Colonnes et valeurs à modifier.
     * @return int Nombre de lignes affectées.
     */
    public function modifier(string $id, array $donnees): int
    {
        $autorises = [
            'etat', 'date_demarrage', 'date_pause', 'date_fin', 'date_cloture',
            'duree_total_compte_a_rebours_secondes', 'temps_pause_cumule_secondes',
        ];
        $this->verifierChamps($donnees, $autorises);

        $affectations = [];
        $parametres = ['id' => $id];

        foreach ($donnees as $champ => $valeur) {
            $affectations[] = $champ . ' = :' . $champ;
            $parametres[$champ] = $valeur;
        }

        $requete = $this->executer(
            'UPDATE parties SET ' . implode(', ', $affectations) . ' WHERE id = :id',
            $parametres
        );

        return $requete->rowCount();
    }

    /**
     * Supprime une partie et les données liées selon les contraintes de clés étrangères.
     *
     * @param string $id Identifiant UUID de la partie.
     * @return int Nombre de lignes supprimées.
     */
    public function supprimer(string $id): int
    {
        return $this->executer('DELETE FROM parties WHERE id = :id', ['id' => $id])->rowCount();
    }
}
