<?php

declare(strict_types=1);

namespace EscapeGame\Service;

use EscapeGame\Api\ApiException;
use EscapeGame\Repository\EquipeRepository;
use EscapeGame\Repository\GroupeRepository;
use EscapeGame\Repository\JournalRepository;
use EscapeGame\Repository\NotificationRepository;
use EscapeGame\Repository\PartieEquipeRepository;
use EscapeGame\Repository\PartieRepository;
use PDO;
use Throwable;

/**
 * Centralise les opérations de consultation et de pilotage d'une partie.
 *
 * Cette classe orchestre les repositories existants. Les pages et scripts
 * clients ne doivent jamais accéder directement à PDO ou construire du SQL.
 */
final class SupervisionService
{
    private PartieRepository $parties;
    private EquipeRepository $equipes;
    private GroupeRepository $groupes;
    private PartieEquipeRepository $suivis;
    private NotificationRepository $notifications;
    private JournalRepository $journal;

    /**
     * Initialise le service avec la connexion PDO partagée par l'application.
     *
     * @param PDO $connexion Connexion créée par bootstrap.php.
     */
    public function __construct(private readonly PDO $connexion)
    {
        $this->parties = new PartieRepository($connexion);
        $this->equipes = new EquipeRepository($connexion);
        $this->groupes = new GroupeRepository($connexion);
        $this->suivis = new PartieEquipeRepository($connexion);
        $this->notifications = new NotificationRepository($connexion);
        $this->journal = new JournalRepository($connexion);
    }

    /**
     * Retourne l'état d'une partie sans exposer les codes secrets des équipes.
     *
     * @param string $partieId Identifiant UUID de la partie.
     * @param int|null $numeroEquipe Si renseigné, limite le résultat à cette équipe.
     * @return array<string, mixed> État de la partie, équipes, groupes, notifications et journal.
     * @throws ApiException Si la partie n'existe pas.
     */
    public function obtenirEtat(string $partieId, ?int $numeroEquipe = null, bool $inclureJournal = true): array
    {
        $partie = $this->parties->obtenirParId($partieId);
        if ($partie === null) {
            throw new ApiException('La partie demandée est introuvable.', 404);
        }

        $equipes = $this->equipes->obtenirParPartie($partieId);
        $equipesRetour = [];
        $idsEquipesAutorisees = [];

        foreach ($equipes as $equipe) {
            $numero = (int) $equipe['numero_equipe'];
            if ($numeroEquipe !== null && $numero !== $numeroEquipe) {
                continue;
            }

            $suivi = $this->suivis->obtenir($partieId, (string) $equipe['id']);
            $groupes = $this->groupes->obtenirParEquipe((string) $equipe['id']);

            $groupesRetour = array_map(
                static fn (array $groupe): array => [
                    'id' => (string) $groupe['id'],
                    'numero_groupe' => (int) $groupe['numero_groupe'],
                    'nom' => (string) $groupe['nom'],
                ],
                $groupes
            );

            $equipesRetour[] = [
                'id' => (string) $equipe['id'],
                'numero_equipe' => $numero,
                'suivi' => $suivi,
                'groupes' => $groupesRetour,
            ];
        }

        $notifications = $this->notifications->obtenirParPartie($partieId);
        if ($numeroEquipe !== null) {
            $equipeAutorisee = null;
            foreach ($equipesRetour as $equipeRetour) {
                if ((int) $equipeRetour['numero_equipe'] === $numeroEquipe) {
                    $equipeAutorisee = $equipeRetour;
                    break;
                }
            }
            $idEquipeAutorisee = $equipeAutorisee['id'] ?? null;
            $idsGroupesAutorises = array_column($equipeAutorisee['groupes'] ?? [], 'id');
            $notifications = array_values(array_filter(
                $notifications,
                static fn (array $notification): bool =>
                    $notification['type_destinataire'] === 'tous'
                    || ($idEquipeAutorisee !== null && ($notification['equipe_id'] ?? null) === $idEquipeAutorisee)
                    || ($idEquipeAutorisee !== null && in_array($notification['groupe_id'] ?? null, $idsGroupesAutorises, true))
            ));
        }
        $journal = $inclureJournal ? $this->journal->obtenirParPartie($partieId) : [];

        return [
            'partie' => $partie,
            'equipes' => $equipesRetour,
            'notifications' => $notifications,
            'journal' => $journal,
        ];
    }

    /**
     * Modifie l'état et les dates associées à une partie dans une transaction.
     *
     * @param string $partieId Identifiant UUID de la partie.
     * @param string $etat Nouvel état : en_attente, en_cours, en_pause ou terminee.
     * @return void
     * @throws ApiException Si l'état est invalide ou si la partie n'existe pas.
     */
    public function modifierEtatPartie(string $partieId, string $etat): void
    {
        $etatsAutorises = ['en_attente', 'en_cours', 'en_pause', 'terminee'];
        if (!in_array($etat, $etatsAutorises, true)) {
            throw new ApiException('L’état demandé est invalide.');
        }

        if ($this->parties->obtenirParId($partieId) === null) {
            throw new ApiException('La partie demandée est introuvable.', 404);
        }

        $donnees = ['etat' => $etat];
        $dateActuelle = date('Y-m-d H:i:s');
        if ($etat === 'en_cours') {
            $donnees['date_demarrage'] = $dateActuelle;
        } elseif ($etat === 'en_pause') {
            $donnees['date_pause'] = $dateActuelle;
        } elseif ($etat === 'terminee') {
            $donnees['date_fin'] = $dateActuelle;
        }

        $this->dansTransaction(function () use ($partieId, $donnees): void {
            $this->parties->modifier($partieId, $donnees);
        });
    }

    /**
     * Modifie les champs de suivi autorisés pour une équipe.
     *
     * @param string $partieId Identifiant UUID de la partie.
     * @param string $equipeId Identifiant UUID de l'équipe.
     * @param array<string, mixed> $donnees Champs de suivi à modifier.
     * @return void
     * @throws ApiException Si les données sont invalides ou si le suivi n'existe pas.
     */
    public function modifierSuiviEquipe(string $partieId, string $equipeId, array $donnees): void
    {
        $champsAutorises = [
            'nombre_tentatives_porte', 'etat_porte', 'porte_ouverte',
            'etat_resolution_annee', 'annee_saisie', 'instant_validation_correcte',
            'temps_resolution_secondes',
        ];
        if ($donnees === [] || array_diff(array_keys($donnees), $champsAutorises) !== []) {
            throw new ApiException('Les champs de suivi transmis ne sont pas autorisés.');
        }

        if ($this->suivis->obtenir($partieId, $equipeId) === null) {
            throw new ApiException('Le suivi de cette équipe est introuvable.', 404);
        }

        if (isset($donnees['etat_porte']) && !in_array($donnees['etat_porte'], ['fermee', 'ouverte', 'explosee', 'reinitialisee'], true)) {
            throw new ApiException('L’état de la porte est invalide.');
        }
        if (isset($donnees['etat_resolution_annee']) && !in_array($donnees['etat_resolution_annee'], ['non_resolue', 'resolue'], true)) {
            throw new ApiException('L’état de résolution est invalide.');
        }
        if (isset($donnees['porte_ouverte']) && !is_bool($donnees['porte_ouverte'])) {
            throw new ApiException('La valeur porte_ouverte doit être booléenne.');
        }
        foreach (['nombre_tentatives_porte', 'annee_saisie', 'temps_resolution_secondes'] as $champNumerique) {
            if (array_key_exists($champNumerique, $donnees)
                && $donnees[$champNumerique] !== null
                && (!is_int($donnees[$champNumerique]) || $donnees[$champNumerique] < 0)) {
                throw new ApiException('Une valeur numérique de suivi est invalide.');
            }
        }

        $this->dansTransaction(function () use ($partieId, $equipeId, $donnees): void {
            $this->suivis->modifier($partieId, $equipeId, $donnees);
        });
    }

    /**
     * Crée une notification pour tous, une équipe ou un groupe.
     *
     * @param string $partieId Identifiant UUID de la partie.
     * @param string $typeDestinataire Type : tous, equipe ou groupe.
     * @param string $typeNotification Catégorie de notification.
     * @param string $contenu Texte de la notification.
     * @param string|null $equipeId Identifiant de l'équipe destinataire.
     * @param string|null $groupeId Identifiant du groupe destinataire.
     * @return string Identifiant de la notification créée.
     * @throws ApiException Si la partie n'existe pas ou si les données sont invalides.
     */
    public function creerNotification(
        string $partieId,
        string $typeDestinataire,
        string $typeNotification,
        string $contenu,
        ?string $equipeId = null,
        ?string $groupeId = null
    ): string {
        if ($this->parties->obtenirParId($partieId) === null) {
            throw new ApiException('La partie demandée est introuvable.', 404);
        }
        if (trim($typeNotification) === '' || trim($contenu) === '' || strlen($contenu) > 20000) {
            throw new ApiException('Le contenu de la notification est invalide.');
        }

        try {
            return $this->dansTransaction(fn (): string => $this->notifications->creer(
                $partieId,
                $typeDestinataire,
                $typeNotification,
                $contenu,
                $equipeId,
                $groupeId
            ));
        } catch (\InvalidArgumentException $exception) {
            throw new ApiException('Le destinataire de la notification est invalide.', 400, $exception);
        }
    }

    /**
     * Marque comme lue une notification appartenant à la partie indiquée.
     *
     * @param string $partieId Identifiant UUID de la partie.
     * @param string $notificationId Identifiant UUID de la notification.
     * @return void
     * @throws ApiException Si la notification n'appartient pas à la partie.
     */
    public function marquerNotificationLue(string $partieId, string $notificationId): void
    {
        $notification = $this->notifications->obtenirParId($notificationId);
        if ($notification === null || (string) $notification['partie_id'] !== $partieId) {
            throw new ApiException('La notification demandée est introuvable.', 404);
        }

        $this->dansTransaction(function () use ($notificationId): void {
            $this->notifications->marquerCommeLue($notificationId);
        });
    }

    /**
     * Exécute une opération atomique et annule les changements en cas d'erreur.
     *
     * @template T
     * @param callable():T $operation Opération métier à exécuter.
     * @return T Résultat de l'opération.
     * @throws Throwable Si l'opération échoue, après annulation de la transaction.
     */
    private function dansTransaction(callable $operation): mixed
    {
        $transactionDejaOuverte = $this->connexion->inTransaction();
        if (!$transactionDejaOuverte) {
            $this->connexion->beginTransaction();
        }

        try {
            $resultat = $operation();
            if (!$transactionDejaOuverte && $this->connexion->inTransaction()) {
                $this->connexion->commit();
            }
            return $resultat;
        } catch (Throwable $exception) {
            if (!$transactionDejaOuverte && $this->connexion->inTransaction()) {
                $this->connexion->rollBack();
            }
            throw $exception;
        }
    }
}
