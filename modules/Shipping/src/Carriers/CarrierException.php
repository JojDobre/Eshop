<?php

namespace Modules\Shipping\Carriers;

use RuntimeException;
use Throwable;

/**
 * Chyba pri komunikácii s kuriérom. Typ určuje reakciu (PLAN.md, kapitola 2.6).
 */
class CarrierException extends RuntimeException
{
    /** Neplatné prístupové údaje alebo chýbajúce nastavenie – opraví obchodník. */
    public const CONFIGURATION = 'configuration';

    /** API kuriéra neodpovedá alebo vracia chybu 5xx – skúsi sa znova neskôr. */
    public const UNAVAILABLE = 'unavailable';

    /** Kuriér odmietol údaje zásielky (napr. neplatné PSČ) – opraví obchodník v objednávke. */
    public const INVALID_DATA = 'invalid_data';

    public function __construct(string $message, public readonly string $type = self::INVALID_DATA, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function isTemporary(): bool
    {
        return $this->type === self::UNAVAILABLE;
    }
}
