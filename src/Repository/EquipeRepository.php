<?php

declare(strict_types=1);

namespace EscapeGame\Repository;

use EscapeGame\Support\Uuid;

/**
 * Gère les équipes rattachées à une partie.
 */
final class EquipeRepository extends AbstractRepository
{
    /**
     * Crée une équipe dans une partie existante.
     *
     * @param string $partieId Identifiant UUID de la partie.
     * @param int $numero Numero de l'équipe, de 1 à 4 selon l'organisation validée.
     * @param array<string, mixed> $parametres Paramètres JSON de l'équipe.
     * @param string|null $phraseCle Phrase-clé de l'équipe.
     * @param string|null $codePorte Code secret ouvrant la porte.
     * @param string|null $codeFinal Code final distinct du code de porte.
     * @return string Identifiant UUID de l'équipe créée.
     */
    public function creer(
        string $partieId,
        int $numero,
        array $parametres = [],
        ?string $phraseCle = null,
        ?string $codePorte = null,
        ?string $codeFinal = null
    ): string {
        $id = Uuid::generer();

        $this->executer(
            'INSERT INTO equipes (id, partie_id, numero_equipe, phrase_cle, code_porte, code_final, parametres)
             VALUES (:id, :partie_id, :numero, :phrase_cle, :code_porte, :code_final, :parametres)',
            [
                'id' => $id,
                'partie_id' => $partieId,
                'numero' => $numero,
                'phrase_cle' => $phraseCle,
                'code_porte' => $codePorte,
                'code_final' => $codeFinal,
                'parametres' => $this->encoderJson($parametres),
            ]
        );

        return $id;
    }

    /**
     * Recherche une équipe par son identifiant.
     *
     * @param string $id Identifiant UUID de l'équipe.
     * @return array<string, mixed>|null Équipe trouvée ou null.
     */
    public function obtenirParId(string $id): ?array
    {
        $equipe = $this->executer(
            'SELECT * FROM equipes WHERE id = :id LIMIT 1',
            ['id' => $id]
        )->fetch();

        if ($equipe === false) {
            return null;
        }

        $equipe['parametres'] = $this->decoderJson($equipe['parametres']);

        return $equipe;
    }

    /**
     * Retourne les équipes d'une partie dans l'ordre de leur numéro.
     *
     * @param string $partieId Identifiant UUID de la partie.
     * @return list<array<string, mixed>> Équipes de la partie.
     */
    public function obtenirParPartie(string $partieId): array
    {
        $equipes = $this->executer(
            'SELECT * FROM equipes WHERE partie_id = :partie_id ORDER BY numero_equipe',
            ['partie_id' => $partieId]
        )->fetchAll();

        foreach ($equipes as &$equipe) {
            $equipe['parametres'] = $this->decoderJson($equipe['parametres']);
        }
        unset($equipe);

        return $equipes;
    }

    /**
     * Modifie les données autorisées d'une équipe.
     *
     * @param string $id Identifiant UUID de l'équipe.
     * @param array<string, mixed> $donnees Colonnes et valeurs à modifier.
     * @return int Nombre de lignes affectées.
     */
    public function modifier(string $id, array $donnees): int
    {
        $autorises = ['phrase_cle', 'code_porte', 'code_final', 'parametres'];
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
            'UPDATE equipes SET ' . implode(', ', $affectations) . ' WHERE id = :id',
            $parametres
        )->rowCount();
    }

    /**
     * Supprime une équipe.
     *
     * @param string $id Identifiant UUID de l'équipe.
     * @return int Nombre de lignes supprimées.
     */
    public function supprimer(string $id): int
    {
        return $this->executer('DELETE FROM equipes WHERE id = :id', ['id' => $id])->rowCount();
    }
}
