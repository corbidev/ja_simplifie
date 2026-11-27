<?php

declare(strict_types=1);

namespace EscapeGame\Api;

/**
 * Applique les contrôles de session, de rôle et de protection CSRF aux routes API.
 */
final class AuthentificationApi
{
    /**
     * Démarre ou réutilise la session sécurisée du projet.
     *
     * @return array{role: string, equipe: int|null} Identité authentifiée.
     * @throws ApiException Si la session est absente ou l'identité invalide.
     */
    public static function obtenirIdentite(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            \Authentification::initialiserSessionSecurisee();
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new ApiException('La session est indisponible.', 401);
        }

        $identite = $_SESSION['authentification'] ?? null;
        if (!is_array($identite) || !isset($identite['role']) || !array_key_exists('equipe', $identite)) {
            throw new ApiException('Authentification requise.', 401);
        }

        $role = $identite['role'];
        $equipe = $identite['equipe'];
        $identiteValide = match ($role) {
            'administrateur' => $equipe === null,
            'tableau_de_bord' => $equipe === 0,
            'equipe' => is_int($equipe) && in_array($equipe, [1, 2, 3, 4], true),
            default => false,
        };

        if (!$identiteValide) {
            throw new ApiException('Authentification requise.', 401);
        }

        return ['role' => $role, 'equipe' => $equipe];
    }

    /**
     * Vérifie que le rôle courant appartient à la liste autorisée.
     *
     * @param array{role: string, equipe: int|null} $identite Identité de session.
     * @param list<string> $rolesAutorises Rôles autorisés pour l'opération.
     * @return void
     * @throws ApiException Si le rôle ne permet pas l'opération.
     */
    public static function exigerRole(array $identite, array $rolesAutorises): void
    {
        if (!in_array($identite['role'], $rolesAutorises, true)) {
            throw new ApiException('Vous n’êtes pas autorisé à effectuer cette opération.', 403);
        }
    }

    /**
     * Vérifie le jeton CSRF transmis dans l'en-tête HTTP dédié.
     *
     * @return void
     * @throws ApiException Si le jeton est absent ou invalide.
     */
    public static function verifierCsrf(): void
    {
        $jeton = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!\Authentification::verifierJetonCsrf($jeton)) {
            throw new ApiException('La requête a expiré. Rechargez la page puis réessayez.', 419);
        }
    }
}
