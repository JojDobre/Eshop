<?php

namespace App\Core\Modules\Exceptions;

use RuntimeException;

/**
 * Chyba pri zapínaní alebo vypínaní modulu (chýbajúca závislosť, nekompatibilná verzia a pod.).
 */
class ModuleException extends RuntimeException
{
    public const NOT_FOUND = 'not_found';

    public const CORE_MODULE = 'core_module';

    public const MISSING_DEPENDENCIES = 'missing_dependencies';

    public const HAS_DEPENDENTS = 'has_dependents';

    public const INCOMPATIBLE = 'incompatible';

    /**
     * @param  string  $reason  Jedna z konštánt triedy (dôvod chyby pre preklad v administrácii).
     * @param  list<string>  $related  Názvy súvisiacich modulov (chýbajúce závislosti alebo závislé moduly).
     */
    public function __construct(string $message, public readonly string $reason, public readonly array $related = [])
    {
        parent::__construct($message);
    }

    public static function notFound(string $name): self
    {
        return new self("Module [{$name}] does not exist.", self::NOT_FOUND);
    }

    public static function coreModule(string $name): self
    {
        return new self("Module [{$name}] is a core module and cannot be disabled.", self::CORE_MODULE);
    }

    /**
     * @param  list<string>  $missing
     */
    public static function missingDependencies(string $name, array $missing): self
    {
        return new self(
            "Module [{$name}] requires modules that are not enabled or compatible: ".implode(', ', $missing).'.',
            self::MISSING_DEPENDENCIES,
            $missing,
        );
    }

    /**
     * @param  list<string>  $dependents
     */
    public static function hasDependents(string $name, array $dependents): self
    {
        return new self(
            "Module [{$name}] cannot be disabled because these enabled modules require it: ".implode(', ', $dependents).'.',
            self::HAS_DEPENDENTS,
            $dependents,
        );
    }

    public static function incompatible(string $name, string $constraint, string $version): self
    {
        return new self("Module [{$name}] requires e-shop version [{$constraint}], current version is [{$version}].", self::INCOMPATIBLE);
    }
}
