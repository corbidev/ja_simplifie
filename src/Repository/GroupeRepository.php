<?php

declare(strict_types=1);

namespace EscapeGame\Repository;

use EscapeGame\Support\Uuid;

/**
 * Gère les quatre groupes rattachés à chaque équipe.
 */
final class GroupeRepository extends AbstractRepository
{
    /**
     * Crée un groupe pour une équipe.
     *
     * @param string $equipeId Identifiant UUID de l'équipe.
     * @param int $numero Numero du groupe dans l'équipe.
     * @param string $nom Nom affiché du groupe.
     * @param array<string, mixed> $parametres Paramètres JSON du groupe.
     * @return string Identifiant UUID du groupe créé.
     */
    public function creer(string $equipeId, int $numero, string $nom, array $parametres = []): string
    {
        $id = Uuid::generer();

        $this->executer(
            'INSERT INTO groupes (id, equipe_id, numero_groupe, nom, parametres)
             VALUES (:id, :equipe_id, :numero, :nom, :parametres)',
            [
                'id' => $id,
                'equipe_id' => $equipeId,
                'numero' => $numero,
                'nom' => $nom,
                'parametres' => $this->encoderJson($parametres),
            ]
        );

        return $id;
    }

    /**
     * Recherche un groupe par son identifiant.
     *
     * @param string $id Identifiant UUID du groupe.
     * @return array<string, mixed>|null Groupe trouvé ou null.
     */
    public function obtenirParId(string $id): ?array
    {
        $groupe = $this->executer(
            'SELECT * FROM groupes WHERE id = :id LIMIT 1',
            ['id' => $id]
        )->fetch();

        if ($groupe === false) {
            return null;
        }

        $groupe['parametres'] = $this->decoderJson($groupe['parametres']);

        return $groupe;
    }

    /**
     * Retourne les groupes d'une équipe dans l'ordre de leur numéro.
     *
     * @param string $equipeId Identifiant UUID de l'équipe.
     * @return list<array<string, mixed>> Groupes de l'équipe.
     */
    public function obtenirParEquipe(string $equipeId): array
    {
        $groupes = $this->executer(
            'SELECT * FROM groupes WHERE equipe_id = :equipe_id ORDER BY numero_groupe',
            ['equipe_id' => $equipeId]
        )->fetchAll();

        foreach ($groupes as &$groupe) {
            $groupe['parametres'] = $this->decoderJson($groupe['parametres']);
        }
        unset($groupe);

        return $groupes;
    }

    /**
     * Modifie le nom ou les paramètres d'un groupe.
     *
     * @param string $id Identifiant UUID du groupe.
     * @param array<string, mixed> $donnees Colonnes et valeurs à modifier.
     * @return int Nombre de lignes affectées.
     */
    public function modifier(string $id, array $donnees): int
    {
        $autorises = ['nom', 'parametres'];
        $this->verifierChamps($donnees, $autorises);

        if (array_key_exists('parametres', $donnees)) {
            $donnees['parametres'] = $this->encoderJson($donnees['parametres']);
        }

        $affectations = [];
        $parametres = ['id' => $id];

        foreach ($donnees as $champ => $valeur) {
            $affectations[] = $champ . ' = :' . $champ;
            $parametres[$champ] = $valeur;
        }

        return $this->executer(
            'UPDATE groupes SET ' . implode(', ', $affectations) . ' WHERE id = :id',
            $parametres
        )->rowCount();
    }

    /**
     * Supprime un groupe.
     *
     * @param string $id Identifiant UUID du groupe.
     * @return int Nombre de lignes supprimées.
     */
    public function supprimer(string $id): int
    {
        return $this->executer('DELETE FROM groupes WHERE id = :id', ['id' => $id])->rowCount();
    }
}
