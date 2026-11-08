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
    public const VERSION = '0.9.0';

    public const MIN_PHP_VERSION = '8.3.0';

    public const MIN_MYSQL_VERSION = '8.0.0';

    public const MIN_MARIADB_VERSION = '10.6.0';
}
