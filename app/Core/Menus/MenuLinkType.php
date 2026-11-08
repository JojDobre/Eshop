<?php

namespace App\Core\Menus;

use Closure;

/**
 * Typ odkazu v ponuke (napr. kategória, stránka). Registrujú ho moduly.
 */
final class MenuLinkType
{
    /**
     * @param  Closure(): string  $label
     * @param  Closure(): array<string, string>  $options  Na výber v administrácii: cieľ => názov.
     * @param  Closure(string): ?array{url: string, label: string}  $resolve  Adresa a názov cieľa (null = cieľ už neexistuje).
     */
    public function __construct(
        public readonly string $key,
        public readonly Closure $label,
        public readonly Closure $options,
        public readonly Closure $resolve,
    ) {}
}
