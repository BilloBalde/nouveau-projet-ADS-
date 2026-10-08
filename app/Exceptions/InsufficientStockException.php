<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Stock insuffisant sur une ou plusieurs lignes. Le message est destiné à
 * l'utilisateur : « Stock insuffisant pour Ciment à Entrepôt 5. Disponible :
 * 10. Demandé : 15. »
 */
class InsufficientStockException extends RuntimeException
{
    /** @param array<int, string> $failures */
    public function __construct(public readonly array $failures)
    {
        parent::__construct(implode(' ', $failures));
    }
}
