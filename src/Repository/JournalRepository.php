<?php

declare(strict_types=1);

namespace EscapeGame\Repository;

use EscapeGame\Support\Uuid;

/**
 * Enregistre et consulte les événements techniques et métier d'une partie.
 */
final class JournalRepository extends AbstractRepository
{
    /**
     * Valeurs autorisées par l'ENUM SQL type_evenement validé pour le projet.
     *
     * @var list<string>
     */
    private const TYPES_EVENEMENT = [
        'partie_creee', 'partie_demarree', 'partie_mise_en_pause', 'partie_reprise', 'partie_cloturee',
        'porte_tentative_code_incorrect', 'porte_ouverte', 'porte_explosee', 'porte_reinitialisee',
        'annee_saisie_incorrecte', 'annee_validee', 'notification_creee', 'notification_lue',
        'erreur_technique', 'exception_php', 'erreur_base_donnees', 'erreur_requete_sql',
        'erreur_validation_donnees', 'erreur_configuration', 'erreur_acces', 'acces_refuse',
        'ressource_introuvable', 'operation_interrompue',
    ];

    /**
     * Ajoute un événement au journal.
     *
     * @param string $partieId Identifiant UUID de la partie.
     * @param string $typeEvenement Type appartenant à l'ENUM validé.
     * @param string $libelle Libellé court de l'événement.
     * @param string|null $equipeId Identifiant éventuel de l'équipe.
     * @param string|null $groupeId Identifiant éventuel du groupe.
     * @param string $niveau Niveau : information, avertissement ou erreur.
     * @param string|null $origine Origine technique ou applicative.
     * @param string|null $acteur Acteur à l'origine de l'événement.
     * @param string|null $description Description complémentaire sans secret.
     * @param array<string, mixed>|null $donnees Données structurées non sensibles.
     * @return string Identifiant UUID de l'événement.
     */
    public function ajouter(
        string $partieId,
        string $typeEvenement,
        string $libelle,
        ?string $equipeId = null,
        ?string $groupeId = null,
        string $niveau = 'information',
        ?string $origine = null,
        ?string $acteur = null,
        ?string $description = null,
        ?array $donnees = null
    ): string {
        if (!in_array($typeEvenement, self::TYPES_EVENEMENT, true)) {
            throw new \InvalidArgumentException('Le type d’événement ne figure pas dans la liste ENUM validée.');
        }

        if (!in_array($niveau, ['information', 'avertissement', 'erreur'], true)) {
            throw new \InvalidArgumentException('Le niveau du journal est invalide.');
        }

        $id = Uuid::generer();

        $this->executer(
            'INSERT INTO journal
             (id, partie_id, equipe_id, groupe_id, type_evenement, niveau, origine, acteur, libelle, description, donnees)
             VALUES (:id, :partie_id, :equipe_id, :groupe_id, :type_evenement, :niveau, :origine, :acteur, :libelle, :description, :donnees)',
            [
                'id' => $id,
                'partie_id' => $partieId,
                'equipe_id' => $equipeId,
                'groupe_id' => $groupeId,
                'type_evenement' => $typeEvenement,
                'niveau' => $niveau,
                'origine' => $origine,
                'acteur' => $acteur,
                'libelle' => $libelle,
                'description' => $description,
                'donnees' => $donnees === null ? null : $this->encoderJson($donnees),
            ]
        );

        return $id;
    }

    /**
     * Retourne les événements d'une partie dans l'ordre chronologique inverse.
     *
     * @param string $partieId Identifiant UUID de la partie.
     * @param int $limite Nombre maximal d'événements.
     * @return list<array<string, mixed>> Événements du journal.
     */
    public function obtenirParPartie(string $partieId, int $limite = 500): array
    {
        $evenements = $this->executer(
            'SELECT * FROM journal
             WHERE partie_id = :partie_id
             ORDER BY date_evenement DESC
             LIMIT :limite',
            ['partie_id' => $partieId, 'limite' => max(1, min($limite, 2000))]
        )->fetchAll();

        foreach ($evenements as &$evenement) {
            $evenement['donnees'] = $this->decoderJson($evenement['donnees']);
        }
        unset($evenement);

        return $evenements;
    }

    /**
     * Supprime un événement du journal par son identifiant.
     *
     * @param string $id Identifiant UUID de l'événement.
     * @return int Nombre de lignes supprimées.
     */
    public function supprimer(string $id): int
    {
        return $this->executer('DELETE FROM journal WHERE id = :id', ['id' => $id])->rowCount();
    }
}
