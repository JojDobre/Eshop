<?php

namespace App\Core;

/**
 * Základné informácie o verzii systému.
 *
 * Verzia sa riadi sémantickým verzovaním (https://semver.org).
 * Skript na zostavenie inštalačného balíka ju číta z tejto konštanty.
 */
final class Eshop
{
    public const VERSION = '1.0.0';

    public const MIN_PHP_VERSION = '8.3.0';

    public const MIN_MYSQL_VERSION = '8.0.0';

    public const MIN_MARIADB_VERSION = '10.6.0';

    /**
     * Verejný kľúč autora (Ed25519, base64), ktorým sa overujú podpísané aktualizácie.
     * Prázdny = podpisy overuje iba kľúč z ESHOP_UPDATE_PUBLIC_KEY v súbore .env.
     */
    public const UPDATE_PUBLIC_KEY = '';
}
