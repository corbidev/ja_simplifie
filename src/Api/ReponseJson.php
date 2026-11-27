<?php

declare(strict_types=1);

namespace EscapeGame\Api;

/**
 * Centralise l'émission des réponses HTTP au format JSON.
 */
final class ReponseJson
{
    /**
     * Envoie une réponse JSON et termine le script.
     *
     * @param array<string, mixed> $donnees Données à encoder.
     * @param int $statutHttp Code HTTP de la réponse.
     * @return never
     */
    public static function envoyer(array $donnees, int $statutHttp = 200): never
    {
        http_response_code($statutHttp);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');

        try {
            echo json_encode(
                $donnees,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } catch (\JsonException) {
            http_response_code(500);
            echo '{"succes":false,"erreur":"La réponse ne peut pas être produite."}';
        }

        exit;
    }
}
