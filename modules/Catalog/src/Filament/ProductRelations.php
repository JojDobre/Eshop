<?php

namespace Modules\Catalog\Filament;

/**
 * Ďalšie záložky (relation managers) pri produkte, ktoré pridávajú iné moduly (napr. blog: súvisiace články).
 */
final class ProductRelations
{
    /** @var list<class-string> */
    private static array $managers = [];

    /**
     * @param  class-string  $manager
     */
    public static function add(string $manager): void
    {
        if (! in_array($manager, self::$managers, true)) {
            self::$managers[] = $manager;
        }
    }

    /**
     * @return list<class-string>
     */
    public static function all(): array
    {
        return self::$managers;
    }
}
