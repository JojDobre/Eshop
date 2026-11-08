<?php

namespace App\Core\Blocks;

use Closure;

/**
 * Typ bloku na skladanie stránok (banner, produkty, text…). Registrujú ho jadro a moduly,
 * vzhľad určuje šablóna súborom blocks/{kľúč}.blade.php.
 */
final class BlockType
{
    /**
     * @param  Closure(): string  $label
     * @param  Closure(): array<int, mixed>  $schema  Polia formulára v administrácii (Filament).
     * @param  (Closure(array<string, mixed>): array<string, mixed>)|null  $prepare  Údaje pre šablónu bloku (napr. načítanie produktov).
     */
    public function __construct(
        public readonly string $key,
        public readonly Closure $label,
        public readonly Closure $schema,
        public readonly ?Closure $prepare = null,
        public readonly string $icon = 'heroicon-o-square-2-stack',
    ) {}
}
