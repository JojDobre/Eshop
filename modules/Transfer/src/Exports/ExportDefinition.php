<?php

namespace Modules\Transfer\Exports;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Exportovateľná entita: stĺpce (kľúč → názov a hodnota) a dotaz s voliteľným obdobím.
 */
final class ExportDefinition
{
    /**
     * @param  Closure(): array<string, array{0: string, 1: Closure(mixed): mixed}>  $columns
     * @param  Closure(?string, ?string): Builder<Model>  $query  (od, do)
     */
    public function __construct(
        public readonly string $key,
        public readonly Closure $label,
        public readonly Closure $columns,
        public readonly Closure $query,
        public readonly bool $hasPeriod = false,
    ) {}
}
