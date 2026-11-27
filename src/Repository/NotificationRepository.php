<?php

declare(strict_types=1);

namespace EscapeGame\Repository;

use EscapeGame\Support\Uuid;

/**
 * Gère les notifications destinées à tous les joueurs, une équipe ou un groupe.
 */
final class NotificationRepository extends AbstractRepository
{
    /**
     * Crée une notification.
     *
     * @param string $partieId Identifiant UUID de la partie.
     * @param string $typeDestinataire Valeur autorisée : tous, equipe ou groupe.
     * @param string $typeNotification Catégorie applicative de la notification.
     * @param string $contenu Contenu textuel de la notification.
     * @param string|null $equipeId Identifiant de l'équipe destinataire.
     * @param string|null $groupeId Identifiant du groupe destinataire.
     * @return string Identifiant UUID de la notification créée.
     */
    public function creer(
        string $partieId,
        string $typeDestinataire,
        string $typeNotification,
        string $contenu,
        ?string $equipeId = null,
        ?string $groupeId = null
    ): string {
        $this->verifierDestinataire($typeDestinataire, $equipeId, $groupeId);
        $id = Uuid::generer();

        $this->executer(
            'INSERT INTO notifications
             (id, partie_id, equipe_id, groupe_id, type_destinataire, type_notification, contenu)
             VALUES (:id, :partie_id, :equipe_id, :groupe_id, :type_destinataire, :type_notification, :contenu)',
            [
                'id' => $id,
                'partie_id' => $partieId,
                'equipe_id' => $equipeId,
                'groupe_id' => $groupeId,
                'type_destinataire' => $typeDestinataire,
                'type_notification' => $typeNotification,
                'contenu' => $contenu,
            ]
        );

        return $id;
    }

    /**
     * Vérifie la cohérence entre le type de destinataire et les identifiants fournis.
     *
     * @param string $type Type de destinataire.
     * @param string|null $equipeId Identifiant éventuel de l'équipe.
     * @param string|null $groupeId Identifiant éventuel du groupe.
     * @return void
     * @throws \InvalidArgumentException Si les identifiants ne correspondent pas au type.
     */
    private function verifierDestinataire(string $type, ?string $equipeId, ?string $groupeId): void
    {
        $coherent = match ($type) {
            'tous' => $equipeId === null && $groupeId === null,
            'equipe' => $equipeId !== null && $groupeId === null,
            'groupe' => $equipeId !== null && $groupeId !== null,
            default => false,
        };

        if (!$coherent) {
            throw new \InvalidArgumentException('Le type de destinataire et les identifiants fournis sont incohérents.');
        }
    }

    /**
     * Recherche une notification par son identifiant.
     *
     * @param string $id Identifiant UUID de la notification.
     * @return array<string, mixed>|null Notification trouvée ou null.
     */
    public function obtenirParId(string $id): ?array
    {
        $notification = $this->executer(
            'SELECT * FROM notifications WHERE id = :id LIMIT 1',
            ['id' => $id]
        )->fetch();

        return $notification === false ? null : $notification;
    }

    /**
     * Retourne les notifications d'une partie, de la plus récente à la plus ancienne.
     *
     * @param string $partieId Identifiant UUID de la partie.
     * @param int $limite Nombre maximal de notifications.
     * @return list<array<string, mixed>> Notifications trouvées.
     */
    public function obtenirParPartie(string $partieId, int $limite = 200): array
    {
        return $this->executer(
            'SELECT * FROM notifications
             WHERE partie_id = :partie_id
             ORDER BY date_creation DESC
             LIMIT :limite',
            ['partie_id' => $partieId, 'limite' => max(1, min($limite, 1000))]
        )->fetchAll();
    }

    /**
     * Marque une notification comme lue.
     *
     * @param string $id Identifiant UUID de la notification.
     * @return int Nombre de lignes affectées.
     */
    public function marquerCommeLue(string $id): int
    {
        return $this->executer(
            'UPDATE notifications SET lu = :lu, date_lecture = CURRENT_TIMESTAMP WHERE id = :id',
            ['lu' => true, 'id' => $id]
        )->rowCount();
    }

    /**
     * Supprime une notification.
     *
     * @param string $id Identifiant UUID de la notification.
     * @return int Nombre de lignes supprimées.
     */
    public function supprimer(string $id): int
    {
        return $this->executer('DELETE FROM notifications WHERE id = :id', ['id' => $id])->rowCount();
    }
}
