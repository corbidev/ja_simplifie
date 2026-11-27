<?php

declare(strict_types=1);

namespace EscapeGame\Api;

use RuntimeException;
use Throwable;

/**
 * Représente une erreur applicative destinée à une réponse HTTP JSON.
 */
final class ApiException extends RuntimeException
{
    /**
     * Construit une erreur API avec son statut HTTP.
     *
     * @param string $message Message public ne contenant aucune donnée sensible.
     * @param int $statutHttp Code HTTP associé à l'erreur.
     * @param Throwable|null $precedente Exception d'origine éventuelle.
     */
    public function __construct(string $message, public readonly int $statutHttp = 400, ?Throwable $precedente = null)
    {
        parent::__construct($message, 0, $precedente);
    }
}
