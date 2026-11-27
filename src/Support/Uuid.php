<?php

declare(strict_types=1);

namespace EscapeGame\Support;

/**
 * Produit des identifiants UUID v4 utilisables dans les clés CHAR(36).
 */
final class Uuid
{
    /**
     * Génère un UUID version 4 conforme au format standard.
     *
     * @return string Identifiant UUID au format 8-4-4-4-12.
     */
    public static function generer(): string
    {
        $octets = random_bytes(16);
        $octets[6] = chr((ord($octets[6]) & 0x0f) | 0x40);
        $octets[8] = chr((ord($octets[8]) & 0x3f) | 0x80);
        $hexadecimal = bin2hex($octets);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hexadecimal, 0, 8),
            substr($hexadecimal, 8, 4),
            substr($hexadecimal, 12, 4),
            substr($hexadecimal, 16, 4),
            substr($hexadecimal, 20, 12)
        );
    }
}
