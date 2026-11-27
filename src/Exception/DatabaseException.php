<?php

declare(strict_types=1);

namespace EscapeGame\Exception;

use RuntimeException;
use Throwable;

/**
 * Représente une erreur survenue pendant l'accès à la base de données.
 */
final class DatabaseException extends RuntimeException
{
    /**
     * Construit une exception d'accès aux données sans exposer de secret.
     *
     * @param string $message Message technique destiné au traitement interne.
     * @param int $code Code de l'erreur.
     * @param Throwable|null $precedente Exception d'origine, si elle existe.
     */
    public function __construct(string $message, int $code = 0, ?Throwable $precedente = null)
    {
        parent::__construct($message, $code, $precedente);
    }
}
